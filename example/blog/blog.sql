-- phpMyAdmin SQL Dump
-- version 3.3.10.5
-- http://www.phpmyadmin.net
--
-- 主机: 127.0.0.1
-- 生成日期: 2015 年 12 月 24 日 09:28
-- 服务器版本: 5.5.30
-- PHP 版本: 5.4.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `blog`
--

-- --------------------------------------------------------

--
-- 表的结构 `demo_posts`
--

CREATE TABLE IF NOT EXISTS `demo_posts` (
  `pid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `uid` int(11) NOT NULL,
  `date_add` datetime NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `demo_posts`
--

INSERT INTO `demo_posts` (`pid`, `title`, `content`, `uid`, `date_add`) VALUES
(1, '这是一个标题', '我是内容', 1, '2015-12-24 17:27:09');

-- --------------------------------------------------------

--
-- 表的结构 `demo_users`
--

CREATE TABLE IF NOT EXISTS `demo_users` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `demo_users`
--

INSERT INTO `demo_users` (`uid`, `username`, `password`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3');
