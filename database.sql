SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `c7www`
--

-- --------------------------------------------------------

--
-- Table structure for table `acl`
--

CREATE TABLE IF NOT EXISTS `acl` (
  `aclid` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(80) NOT NULL,
  `binding` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `groupid` int(11) DEFAULT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`aclid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=116 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  `updated` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=776245 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_accessgroup`
--

CREATE TABLE IF NOT EXISTS `forum_accessgroup` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_bookmark`
--

CREATE TABLE IF NOT EXISTS `forum_bookmark` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `topic_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`topic_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=608 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_forum`
--

CREATE TABLE IF NOT EXISTS `forum_forum` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `group_id` tinyint(3) NOT NULL,
  `last_post_id` int(11) NOT NULL,
  `last_post` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_post_by` int(10) NOT NULL,
  `topics` int(10) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `order` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `last_post_id` (`last_post_id`),
  KEY `last_post_by` (`last_post_by`),
  KEY `order` (`order`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Topics in group' AUTO_INCREMENT=43 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_friends`
--

CREATE TABLE IF NOT EXISTS `forum_friends` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `friend_id` int(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`user_id`),
  KEY `friendid` (`friend_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4255 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_group`
--

CREATE TABLE IF NOT EXISTS `forum_group` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT '0',
  `public` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `order` (`order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_group_acl`
--

CREATE TABLE IF NOT EXISTS `forum_group_acl` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `group_id` tinyint(3) NOT NULL,
  `accessgroup_id` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Links permission between forum groups and access groups' AUTO_INCREMENT=102 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_pm_messages`
--

CREATE TABLE IF NOT EXISTS `forum_pm_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sent_date` datetime DEFAULT NULL,
  `read_date` datetime DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('draft','sent','read') NOT NULL DEFAULT 'draft',
  `inbox` smallint(1) NOT NULL DEFAULT '1',
  `outbox` smallint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `sender_outbox_idx` (`sender_id`,`outbox`),
  KEY `receiver_inbox_idx` (`receiver_id`,`inbox`),
  KEY `status_idx` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2657 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_post`
--

CREATE TABLE IF NOT EXISTS `forum_post` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) NOT NULL,
  `parent_id` int(10) NOT NULL DEFAULT '0',
  `content` mediumtext NOT NULL,
  `user_id` int(8) NOT NULL,
  `updated_by` int(10) NOT NULL,
  `updated` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `topic_id` (`topic_id`),
  KEY `parent_id` (`parent_id`),
  KEY `updated_by` (`updated_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=227241 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_profile`
--

CREATE TABLE IF NOT EXISTS `forum_profile` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `alias` varchar(48) DEFAULT NULL,
  `title` varchar(32) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `bio` varchar(1024) DEFAULT NULL,
  `location` varchar(80) DEFAULT NULL,
  `joined` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_visit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `real_name` varchar(100) DEFAULT NULL,
  `posts` int(6) NOT NULL,
  `www` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `skype` varchar(80) DEFAULT NULL,
  `facebook` varchar(80) DEFAULT NULL,
  `twitter` varchar(80) DEFAULT NULL,
  `jabber` varchar(80) DEFAULT NULL,
  `msn` varchar(80) DEFAULT NULL,
  `icq` varchar(12) DEFAULT NULL,
  `gravatar` tinyint(1) NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `moderator` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `posts` (`posts`),
  KEY `banned` (`banned`),
  KEY `admin` (`admin`,`moderator`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61814 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_profile_acl`
--

CREATE TABLE IF NOT EXISTS `forum_profile_acl` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) NOT NULL,
  `accessgroup_id` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_id` (`profile_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=379 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_registration`
--

CREATE TABLE IF NOT EXISTS `forum_registration` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(40) NOT NULL,
  `email` varchar(150) NOT NULL,
  `hash` varchar(40) NOT NULL,
  `specialtrigger` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=48316 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_resetpassword`
--

CREATE TABLE IF NOT EXISTS `forum_resetpassword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resetcode` varchar(64) NOT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_created` (`ip`,`created`),
  KEY `user_created` (`user_id`,`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topic`
--

CREATE TABLE IF NOT EXISTS `forum_topic` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `first_post_id` int(11) DEFAULT NULL,
  `last_post_id` int(11) DEFAULT NULL,
  `last_post_by` int(10) NOT NULL,
  `last_post` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `forum_id` tinyint(4) NOT NULL,
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `pinned` tinyint(1) NOT NULL DEFAULT '0',
  `views` int(8) NOT NULL DEFAULT '0',
  `posts` int(6) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `first_post_id` (`first_post_id`),
  UNIQUE KEY `last_post_id` (`last_post_id`),
  KEY `title` (`title`),
  KEY `last_post_by` (`last_post_by`),
  KEY `forum_id` (`forum_id`),
  KEY `last_post` (`last_post`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16465 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_unread`
--

CREATE TABLE IF NOT EXISTS `forum_unread` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `topic_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`topic_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1705929 ;

-- --------------------------------------------------------

--
-- Table structure for table `groupfeatures`
--

CREATE TABLE IF NOT EXISTS `groupfeatures` (
  `groupfeatureid` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `value` longtext NOT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`groupfeatureid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `groupid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `import_forum`
--

CREATE TABLE IF NOT EXISTS `import_forum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_id` (`import_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- Table structure for table `import_group`
--

CREATE TABLE IF NOT EXISTS `import_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_id` (`import_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `import_pm`
--

CREATE TABLE IF NOT EXISTS `import_pm` (
  `id` int(11) NOT NULL,
  `import_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_idx` (`import_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `import_post`
--

CREATE TABLE IF NOT EXISTS `import_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_id` (`import_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=200783 ;

-- --------------------------------------------------------

--
-- Table structure for table `import_topic`
--

CREATE TABLE IF NOT EXISTS `import_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_id` (`import_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14488 ;

-- --------------------------------------------------------

--
-- Table structure for table `import_user`
--

CREATE TABLE IF NOT EXISTS `import_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_id` (`import_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21567 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `priority` int(2) NOT NULL DEFAULT '6',
  `facility` varchar(40) NOT NULL,
  `type` varchar(60) DEFAULT NULL,
  `description` text,
  `ip` varchar(40) NOT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`logid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=209929 ;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE IF NOT EXISTS `modules` (
  `moduleid` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(100) DEFAULT NULL,
  `name` varchar(80) DEFAULT NULL,
  `public` smallint(1) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  `binding` varchar(80) DEFAULT NULL,
  `external` smallint(1) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `hidden` smallint(1) DEFAULT NULL,
  `disabled` smallint(1) DEFAULT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`moduleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `navi`
--

CREATE TABLE IF NOT EXISTS `navi` (
  `navid` int(11) NOT NULL AUTO_INCREMENT,
  `parentid` int(11) NOT NULL,
  `orderid` int(11) NOT NULL,
  `localeid` text NOT NULL,
  `module` varchar(80) NOT NULL,
  `params` varchar(255) DEFAULT NULL,
  `uri` text,
  `hidden` int(1) NOT NULL,
  `admin` varchar(3) NOT NULL DEFAULT 'all',
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`navid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `userfeature`
--

CREATE TABLE IF NOT EXISTS `userfeature` (
  `featureid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `keyword` varchar(60) NOT NULL,
  `value` longtext NOT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`featureid`),
  KEY `useridsnkeywords` (`userid`,`keyword`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17623 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(70) DEFAULT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(40) DEFAULT NULL,
  `auth` varchar(255) DEFAULT NULL,
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`userid`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61814 ;

-- --------------------------------------------------------

--
-- Table structure for table `warsow_server`
--

CREATE TABLE IF NOT EXISTS `warsow_server` (
  `id` int(24) NOT NULL AUTO_INCREMENT,
  `name_plain` varchar(255) NOT NULL,
  `name_parsed` varchar(1024) NOT NULL,
  `gametype` varchar(32) NOT NULL,
  `num_players` int(4) NOT NULL,
  `max_players` int(4) NOT NULL,
  `map` varchar(32) DEFAULT NULL,
  `match_state` varchar(150) DEFAULT NULL,
  `server_address` varchar(255) NOT NULL,
  `server_location` varchar(4) DEFAULT NULL,
  `last_update` int(40) NOT NULL,
  `insta` int(1) NOT NULL DEFAULT '0',
  `tv` int(1) NOT NULL DEFAULT '0',
  `matchinfo_player1` varchar(255) DEFAULT NULL,
  `matchinfo_player1score` int(4) DEFAULT NULL,
  `matchinfo_player2` varchar(255) DEFAULT NULL,
  `matchinfo_player2score` int(4) DEFAULT NULL,
  `matchinfo_matchtime` varchar(255) DEFAULT NULL,
  `hide` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_address` (`server_address`),
  KEY `name_plain` (`name_plain`),
  KEY `insta` (`insta`),
  KEY `tb` (`tv`),
  KEY `parsed` (`name_parsed`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8 AUTO_INCREMENT=269495 ;

-- --------------------------------------------------------

--
-- Table structure for table `warsow_server_players`
--

CREATE TABLE IF NOT EXISTS `warsow_server_players` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `server_id` int(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `name_parsed` varchar(255) NOT NULL,
  `score` int(3) NOT NULL,
  `team` tinyint(1) NOT NULL,
  `ping` int(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1185148 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki`
--

CREATE TABLE IF NOT EXISTS `wiki` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_title` varchar(64) NOT NULL,
  `document_content` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL,
  `revision` int(11) NOT NULL DEFAULT '1',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `document_title` (`document_title`,`user_id`),
  KEY `revision` (`revision`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1054 ;

-- --------------------------------------------------------

--
-- Table structure for table `wiki_files`
--

CREATE TABLE IF NOT EXISTS `wiki_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `uploader` int(11) NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `filename` (`filename`),
  KEY `deleted` (`deleted`),
  KEY `uploader` (`uploader`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
