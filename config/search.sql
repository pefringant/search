CREATE TABLE IF NOT EXISTS `search_index` (
  `id` int(10) NOT NULL auto_increment,
  `model` varchar(100) default NULL,
  `model_id` int(10) default NULL,
  `data` longtext default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `association_key` (`model`,`model_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM;