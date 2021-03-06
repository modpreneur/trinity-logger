<?php
/*
 * This file is part of the Trinity project.
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Services;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Trinity\Bundle\LoggerBundle\Entity\ExceptionLog;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class DatabaseHandler. Takes care of not caught exceptions and store them in
 * configured elasticSearch database.
 */
class DatabaseHandler extends AbstractProcessingHandler
{
    /** @var  TokenStorageInterface */
    private $tokenStorage;

    /** @var  Session */
    protected $session;

    /** @var RequestStack */
    private $requestStack;

    /** @var  ElasticLogService */
    private $esLogger;

    /** @var string */
    private $system;


    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param ElasticLogService $esLogger
     * @param int $level = Logger::DEBUG
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     * @param string $system
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        ElasticLogService $esLogger,
        $level = Logger::DEBUG,
        $bubble = true,
        $system = 'unknown source'
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->esLogger = $esLogger;

        $this->system = $system;

        parent::__construct($level, $bubble);
    }


    /**
     * @param array $record
     */
    protected function write(array $record): void
    {
        if ('doctrine' === $record['channel']) {
            if ((int)$record['level'] >= Logger::WARNING) {
                /* not forgotten debug statement */
                \error_log($record['message']);
            }

            return;
        }
        if ((int)$record['level'] >= Logger::ERROR) {
            //exception is logged twice, get rid of 'Uncaught...' version
            if (\strpos($record['message'], 'Uncaught') === 0) {
                return;
            }
            $exception = new ExceptionLog();
            /** @var Request $request */
            $request = $this->requestStack->getCurrentRequest();

            $serverData = '';

            $url = null;
            $ip = null;

            if (isset($record['extra']['serverData'])) {
                $serverData = $record['extra']['serverData'];
                $ip = $this->getFromServerData('HTTP_X_FORWARDED_FOR', $serverData);
            }
            if ($request) {
                $url = $request->getUri();
                $ip = $ip ?: $request->getClientIp();
            } else {
                $url = $this->getFromServerData('REQUEST_URI', $serverData);
            }
            $readable = $this->getReadable($record);

            /*
             * Elastic part
             */
            //log, level, serverData, created, url, ip, user_id, readable
            $exception->setLog($record['message']);
            $exception->setLevel($record['level']);
            $exception->setServerData($serverData);
            $exception->setCreatedAt(new \DateTime);
            $exception->setUrl($url);
            $exception->setIp($ip);
            $exception->setSystem($this->system);

            $user = $this->getUser();
            if ($user !== null) {
                $exception->setUser($user);
            }

            $exception->setReadable($readable);
            $this->esLogger->writeInto(ExceptionLog::LOG_NAME, $exception);
        }
    }


    /**
     * @param array $e
     *
     * @return string
     */
    private function getReadable($e): string
    {
        /*
         * Known SQL codes:
         * https://www-304.ibm.com/support/knowledgecenter/SSEPEK_10.0.0/com.ibm.db2z10.doc.codes/src/tpc/db2z_sqlstatevalues.dita
         */
        $sqlTag = 'PDOException';

        if (0 === \strncmp($e['message'], $sqlTag, \strlen($sqlTag))) {
            return $this->processPDO($e['message']);
        }
        //When readable format is not supported yet
        return '';
    }


    /**
     * @param string $errorMessage
     *
     * @return string
     */
    private function processPDO(string $errorMessage): string
    {
        $line = \strstr($errorMessage, \PHP_EOL, true);
        $short = \substr($line, \strpos($line, 'R: ') + 4);
        $readable = \ucfirst($short);
        if ($readable && $this->session->isStarted()) {
            $this->session->set('readable', $readable);
        }
        return $readable;
    }

    /**
     * Get user either from the token storage or from the session if the storage is empty.
     *
     * @return null|UserInterface
     */
    private function getUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() &&
            !is_string($token->getUser()) &&
            $token->getUser() instanceof UserInterface
        ) {
            return $token->getUser();
        }

        return $this->getUserFromSession();
    }

    /**
     * Tries to get the user from the session.
     *
     * The session must have at least one key starting with the '_security_' string.
     * The key should contain an serialized instance of TokenInterface
     *
     *
     * @return UserInterface|null
     */
    private function getUserFromSession(): ?UserInterface
    {
        //when the session is not started(due to an exception or other reasons)
        //the session tries to start and that causes issues
        //for example when building cache form the console:
        //[RuntimeException]
        //Failed to start the session because headers have already been sent by "/var/app/bin/console" at line 2.
        if (!$this->session->isStarted()) {
            return null;
        }

        $sessionData = $this->session->all();

        foreach ($sessionData as $key => $value) {
            if (0 === \strpos($key, '_security_')) { // see Symfony\Component\Security\Http\Firewall\ContextListener
                //the key starts with the _security_, so it should contain the token

                $value = \unserialize($value, [TokenInterface::class]); //unserialize only instances of TokenInterface

                if (false === $value) { //unsuccessful unserialization
                    break;
                }

                if ($value->getUser() instanceof UserInterface) {
                    return $value->getUser();
                }

                break; //the security key was already found, so there is no need to traverse the whole array
            }
        }

        return null;
    }


    /**
     * @param string $valueName
     * @param string $serverData
     *
     * @return string
     */
    private function getFromServerData(string $valueName, string $serverData) :string
    {
        $beginOfLine = \strpos($serverData, $valueName);
        if ($beginOfLine !== false) {
            $beginOfValue = $beginOfLine + \strlen($valueName) + 2; //after name is colon and space = two characters.
            $endLine = \strpos($serverData, \PHP_EOL, $beginOfValue);   //end of line after each pair
            return \substr($serverData, $beginOfValue, $endLine - $beginOfValue) ?: '';
        }
        return '';
    }
}
