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
    /** @var  ElasticLogServiceWithTtl */
    private $esLogger;
    /** @var string */
    private $system;


    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param ElasticLogServiceWithTtl $esLogger
     * @param int $level = Logger::DEBUG
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     * @param string $system
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        ElasticLogServiceWithTtl $esLogger,
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
                error_log($record['message']);
            }

            return;
        }
        if ((int)$record['level'] >= Logger::ERROR) {
            //exception is logged twice, get rid of 'Uncaught...' version
            if (strpos($record['message'], 'Uncaught') === 0) {
                return;
            }
            $exception = new ExceptionLog();
            /** @var Request $request */
            $request = $this->requestStack->getCurrentRequest();
            $url = null;
            $ip = null;
            if ($request) {
                $url = $request->getUri();
                $ip = $request->getClientIp();
            } else {
                $requestedUrl = strpos($record['extra']['serverData'], 'REQUEST_URI:');
                if ($requestedUrl !== false) {
                    $requestedUrl += strlen('REQUEST_URI: ');
                    $endLine = strpos($record['extra']['serverData'], PHP_EOL, $requestedUrl);
                    $url = substr($record['extra']['serverData'], $requestedUrl, $endLine - $requestedUrl);
                }
                /*
                 * todo: get ip from extra too (which one?)
                 */
            }
            $token = $this->tokenStorage->getToken();
            $readable = $this->getReadable($record);
            $serverData = '';

            if (isset($record['extra']['serverData'])) {
                $serverData = $record['extra']['serverData'];
            }

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
            if ($token && $token->getUser() && !is_string($token->getUser())) {
                if ($token->getUser() instanceof UserInterface) {
                    $exception->setUser($token->getUser());
                }
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

        if (0 === strncmp($e['message'], $sqlTag, strlen($sqlTag))) {
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
        $line = strstr($errorMessage, PHP_EOL, true);
        $short = substr($line, strpos($line, 'R: ') + 4);
        $readable = ucfirst($short);
        if ($readable && $this->session->isStarted()) {
            $this->session->set('readable', $readable);
        }
        return $readable;
    }
}
