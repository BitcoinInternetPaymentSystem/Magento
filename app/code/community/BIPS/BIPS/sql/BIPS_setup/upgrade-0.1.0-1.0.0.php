<?php

$installer = $this;
$installer->startSetup(); 

$installer->run("
CREATE TABLE IF NOT EXISTS `BIPS_ipns` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `quote_id` int(11) unsigned default NULL,
  `order_id` int(11) unsigned default NULL,
  `invoice` int(11) NOT NULL,
  `txid` varchar(255) NOT NULL,
  `price` decimal(15,8) NOT NULL,
  `status` TINYINT(1) NOT NULL,
  `timestamp` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `quote_id` (`invoice`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
 
");
 
$installer->endSetup();