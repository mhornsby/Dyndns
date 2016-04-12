<?php

require 'vendor/autoload.php';

$dyndns = new Dyndns\Server();

// Configuration
$dyndns
  ->setConfig('hostFile', '/etc/dyndns/dyndns.host') // host database
  ->setConfig('userFile', '/etc/dyndns/dyndns.user')   // user database
  ->setConfig('apikeyFile', '/etc/dyndns/linode.api_key')   // linode api  database
  ->setConfig('domainidFile', '/etc/dyndns/linode.domainid')   // linode domainid
  ->setConfig('resourceidFile', '/etc/dyndns/linode.resourceid')   // linode resourceid
  ->setConfig('debug', true)  // enable debugging
  ->setConfig('debugFile', '/tmp/dyndns.log') // debug file
;

$dyndns->init();
