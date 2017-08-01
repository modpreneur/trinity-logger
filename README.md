# trinity-logger 

# This bundle is reconstructed for new elasticsearch (v5) and the read me is not actualized yet. TODO

[![Coverage Status](https://coveralls.io/repos/github/modpreneur/trinity-logger/badge.svg?branch=master)](https://coveralls.io/github/modpreneur/trinity-logger?branch=master)
[![Build Status](https://travis-ci.org/modpreneur/trinity-logger.svg?branch=master)](https://travis-ci.org/modpreneur/trinity-settings)

Bundle for storing and loading logs in ElasticSearch database.

Write issue if you found something that doesn't work as it should.

Logger creates new index each day in format YYYY-MM-DD. It works with environment and as for now two types are used. Development and Production logs are written into same index.
The test environment adds prefix 'test-' before the index.

Thanks to this the logs may be viewed directly by using URL:

    http:\\elastic_host:port\YYYY-MM-DD\LogName\LogId

for example:

    

    trinity_logger:
      elastic_logs:
        elastic_host: '127.0.0.1:9200'
        managed_index: 'necktie'

Configured index can be changed when method is called.

  Classic call:

    $this->get('trinity.elastic.read.log.service')->getCount('ExceptionLog');
    
  Call different index:

    $this->get('trinity.elastic.read.log.service')->setIndex('venice')->getCount('ExceptionLog');

For searching it is required to know where is entity class stored. Most classes are stored on same place
and therefor they can be added into configuration as follow:

    trinity_logger:
      elastic_logs:
        elastic_host: '127.0.0.1:9200'
        managed_index: 'necktie'
        entities_path: 'Necktie\\AppBundle\\Entity'

When we use nqlQuery (see trinity/search for more about used nqlQuery) we can call:

    $entities = $this->get('trinity.elastic.read.log.service')->getByQuery($nqlQuery);

If entity definition is stored elsewhere, we go for example as this:

    $entities = $this->get('trinity.elastic.read.log.service')
        ->setEntityPath('Necktie\\PaymentBundle\\Entity')
        ->getByQuery($nqlQuery);

Note: ElasticSearch is noSQL database.... NO!!!SQL therefor it will go with only queries under one entity.
If you need join something use relation based SQL database.

As main use of this bundle for now is Necktie, following values are used as default and doesn't require
configuration.
 managed_index: 'necktie'
 entities_path: 'Necktie\\AppBundle\\Entity'
