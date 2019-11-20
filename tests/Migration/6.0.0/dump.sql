# ************************************************************
# Advanced Access Manager DB Dump
# Version 5
#
# Generation Time: 2019-10-27 14:58:39 +0000
# ************************************************************


# ------------------------------------------------------------
# Dump of table wp_options
# ------------------------------------------------------------

LOCK TABLES `wp_options` WRITE;

INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`)
VALUES
	('aam_menu_role_administrator','a:8:{s:15:\"menu-upload.php\";s:1:\"1\";i:943136475;s:1:\"1\";s:10:\"upload.php\";s:1:\"1\";i:3218358587;s:1:\"1\";s:13:\"media-new.php\";s:1:\"1\";i:867269274;s:1:\"1\";s:11:\"widgets.php\";s:1:\"1\";i:1421817448;s:1:\"1\";}','yes'),
	('aam_metabox_role_administrator','a:6:{s:23:\"widgets|WP_Widget_Pages\";s:1:\"1\";i:1287392057;s:1:\"1\";s:29:\"dashboard|dashboard_right_now\";s:1:\"1\";i:3777372316;s:1:\"1\";s:21:\"post|tagsdiv-post_tag\";s:1:\"1\";i:2077369295;s:1:\"1\";}','yes'),
	('aam_type_post_role_administrator','a:52:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:17:\"post|backend.edit\";s:1:\"1\";s:19:\"term|backend.delete\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:15:\"term|api.delete\";s:1:\"1\";s:18:\"post|frontend.list\";s:1:\"1\";s:25:\"post|frontend.list_others\";s:1:\"1\";s:25:\"post|frontend.read_others\";s:1:\"1\";s:20:\"post|frontend.teaser\";s:27:\"You are not allowed to read\";s:19:\"post|frontend.limit\";s:1:\"1\";s:34:\"post|frontend.access_counter_limit\";s:2:\"10\";s:28:\"post|frontend.access_counter\";s:1:\"1\";s:21:\"post|frontend.comment\";s:1:\"1\";s:22:\"post|frontend.location\";s:11:\"url|302|302\";s:22:\"post|frontend.redirect\";s:1:\"1\";s:22:\"post|frontend.password\";s:6:\"123456\";s:23:\"post|frontend.protected\";s:1:\"1\";s:29:\"post|frontend.expire_datetime\";s:20:\"10/28/2019, 10:37 am\";s:20:\"post|frontend.expire\";s:1:\"1\";s:18:\"term|frontend.list\";s:1:\"1\";s:17:\"post|backend.list\";s:1:\"1\";s:24:\"post|backend.list_others\";s:1:\"1\";s:16:\"post|backend.add\";s:1:\"1\";s:24:\"post|backend.edit_others\";s:1:\"1\";s:19:\"post|backend.delete\";s:1:\"1\";s:26:\"post|backend.delete_others\";s:1:\"1\";s:20:\"post|backend.publish\";s:1:\"1\";s:27:\"post|backend.publish_others\";s:1:\"1\";s:17:\"term|backend.list\";s:1:\"1\";s:17:\"term|backend.edit\";s:1:\"1\";s:13:\"post|api.list\";s:1:\"1\";s:20:\"post|api.list_others\";s:1:\"1\";s:20:\"post|api.read_others\";s:1:\"1\";s:12:\"post|api.add\";s:1:\"1\";s:13:\"post|api.edit\";s:1:\"1\";s:20:\"post|api.edit_others\";s:1:\"1\";s:15:\"post|api.delete\";s:1:\"1\";s:22:\"post|api.delete_others\";s:1:\"1\";s:15:\"post|api.teaser\";s:11:\"Not allowed\";s:14:\"post|api.limit\";s:1:\"1\";s:29:\"post|api.access_counter_limit\";s:2:\"11\";s:23:\"post|api.access_counter\";s:1:\"1\";s:16:\"post|api.comment\";s:1:\"1\";s:17:\"post|api.location\";s:26:\"callback|Callback::trigger\";s:17:\"post|api.redirect\";s:1:\"1\";s:17:\"post|api.password\";s:7:\"1234567\";s:18:\"post|api.protected\";s:1:\"1\";s:24:\"post|api.expire_datetime\";s:20:\"11/07/2019, 10:39 am\";s:15:\"post|api.expire\";s:1:\"1\";s:13:\"term|api.list\";s:1:\"1\";s:13:\"term|api.edit\";s:1:\"1\";}','yes'),
	('aam_redirect_role_administrator','a:4:{s:22:\"frontend.redirect.type\";s:4:\"page\";s:22:\"frontend.redirect.page\";s:1:\"2\";s:21:\"backend.redirect.type\";s:7:\"message\";s:24:\"backend.redirect.message\";s:16:\"Access is denied\";}','yes'),
	('aam_loginredirect_role_administrator','a:2:{s:19:\"login.redirect.type\";s:3:\"url\";s:18:\"login.redirect.url\";s:18:\"https://google.com\";}','yes'),
	('aam_logoutredirect_role_administrator','a:2:{s:20:\"logout.redirect.type\";s:8:\"callback\";s:24:\"logout.redirect.callback\";s:11:\"test::hello\";}','yes'),
	('aam_route_role_administrator','a:1:{s:23:\"restful|/oembed/1.0|get\";s:1:\"1\";}','yes'),
	('aam_uri_role_administrator','a:1:{s:13:\"5db5a46c9dc3e\";a:4:{s:3:\"uri\";s:11:\"/*/category\";s:4:\"type\";s:4:\"page\";s:6:\"action\";s:1:\"2\";s:4:\"code\";s:3:\"302\";}}','yes'),
	('aam_toolbar_role_administrator','a:1:{s:13:\"documentation\";s:1:\"1\";}','yes'),
	('aam_visitor_metabox','a:2:{s:28:\"widgets|WP_Widget_Categories\";s:1:\"1\";i:3349423736;s:1:\"1\";}','yes'),
	('aam_visitor_type_post','a:4:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:13:\"term|api.list\";s:1:\"1\";}','yes'),
	('aam_visitor_term_1|category','a:7:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:13:\"term|api.list\";s:1:\"0\";s:18:\"term|frontend.list\";s:1:\"1\";s:18:\"post|frontend.list\";s:1:\"1\";s:13:\"post|api.list\";s:1:\"1\";}','yes'),
	('aam_visitor_redirect','a:1:{s:22:\"frontend.redirect.type\";s:5:\"login\";}','yes'),
	('aam_visitor_route','a:1:{s:33:\"restful|/aam/v1/authenticate|post\";s:1:\"1\";}','yes'),
	('aam_visitor_uri','a:1:{s:13:\"5db5a6da7ef9a\";a:4:{s:3:\"uri\";s:12:\"/sample-page\";s:4:\"type\";s:5:\"login\";s:6:\"action\";N;s:4:\"code\";s:0:\"\";}}','yes'),
	('aam_visitor_ipCheck','a:1:{s:32:\"766cb6abf97c586b343f3119dcec787c\";a:3:{s:4:\"type\";s:2:\"ip\";s:4:\"rule\";s:9:\"127.0.0.1\";s:4:\"mode\";s:1:\"0\";}}','yes'),
	('aam_menu_default','a:8:{s:28:\"menu-edit.php?post_type=page\";s:1:\"1\";i:193563359;s:1:\"1\";s:23:\"edit.php?post_type=page\";s:1:\"1\";i:1374775093;s:1:\"1\";s:27:\"post-new.php?post_type=page\";s:1:\"1\";i:497848836;s:1:\"1\";s:11:\"widgets.php\";s:1:\"1\";i:1421817448;s:1:\"1\";}','yes'),
	('aam_toolbar_default','a:1:{s:14:\"support-forums\";s:1:\"1\";}','yes'),
	('aam_metabox_default','a:6:{s:26:\"widgets|WP_Widget_Archives\";s:1:\"1\";i:3466595345;s:1:\"1\";s:27:\"dashboard|dashboard_primary\";s:1:\"1\";i:279696297;s:1:\"1\";s:16:\"post|postexcerpt\";s:1:\"1\";i:338020624;s:1:\"1\";}','yes'),
	('aam_type_post_default','a:6:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:16:\"post|backend.add\";s:1:\"1\";s:17:\"term|backend.list\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:13:\"term|api.edit\";s:1:\"1\";}','yes'),
	('aam_term_1|category_default','a:12:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:16:\"post|backend.add\";s:1:\"1\";s:17:\"term|backend.list\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:13:\"term|api.edit\";s:1:\"1\";s:18:\"term|frontend.list\";s:1:\"1\";s:18:\"post|frontend.list\";s:1:\"1\";s:17:\"term|backend.edit\";s:1:\"1\";s:17:\"post|backend.edit\";s:1:\"1\";s:13:\"term|api.list\";s:1:\"1\";s:13:\"post|api.list\";s:1:\"1\";}','yes'),
	('aam_redirect_default','a:4:{s:22:\"frontend.redirect.type\";s:7:\"message\";s:25:\"frontend.redirect.message\";s:19:\"You are not allowed\";s:21:\"backend.redirect.type\";s:4:\"page\";s:21:\"backend.redirect.page\";s:1:\"2\";}','yes'),
	('aam_loginredirect_default','a:2:{s:19:\"login.redirect.type\";s:4:\"page\";s:19:\"login.redirect.page\";s:1:\"2\";}','yes'),
	('aam_logoutredirect_default','a:2:{s:20:\"logout.redirect.type\";s:3:\"url\";s:19:\"logout.redirect.url\";s:18:\"https://google.com\";}','yes'),
	('aam_route_default','a:1:{s:33:\"restful|/aam/v1/validate-jwt|post\";s:1:\"1\";}','yes'),
	('aam-utilities','a:2:{s:25:\"frontend.404redirect.type\";s:8:\"callback\";s:29:\"frontend.404redirect.callback\";s:17:\"Callback::trigger\";}','yes'),
	('aam_uri_default','a:1:{s:13:\"5db5a7f1d74ae\";a:4:{s:3:\"uri\";s:11:\"/first-post\";s:4:\"type\";s:4:\"deny\";s:6:\"action\";N;s:4:\"code\";s:0:\"\";}}','yes'),
	('aam_taxonomy_post|category_role_administrator','a:1:{s:7:\"default\";s:1:\"2\";}','yes'),
	('aam-configpress','[aam]\ntest.property = \"Migration\"','yes'),
	('aam_term_1|category_role_administrator','a:52:{s:18:\"post|frontend.read\";s:1:\"0\";s:20:\"term|frontend.browse\";s:1:\"0\";s:17:\"post|backend.edit\";s:1:\"0\";s:19:\"term|backend.delete\";s:1:\"0\";s:13:\"post|api.read\";s:1:\"0\";s:15:\"term|api.delete\";s:1:\"0\";s:18:\"post|frontend.list\";s:1:\"0\";s:25:\"post|frontend.list_others\";s:1:\"0\";s:25:\"post|frontend.read_others\";s:1:\"0\";s:20:\"post|frontend.teaser\";s:27:\"You are not allowed to read\";s:19:\"post|frontend.limit\";s:1:\"0\";s:34:\"post|frontend.access_counter_limit\";s:2:\"10\";s:28:\"post|frontend.access_counter\";s:1:\"0\";s:21:\"post|frontend.comment\";s:1:\"0\";s:22:\"post|frontend.location\";s:11:\"url|302|302\";s:22:\"post|frontend.redirect\";s:1:\"0\";s:22:\"post|frontend.password\";s:6:\"123456\";s:23:\"post|frontend.protected\";s:1:\"0\";s:29:\"post|frontend.expire_datetime\";s:20:\"10/28/2019, 10:37 am\";s:20:\"post|frontend.expire\";s:1:\"0\";s:18:\"term|frontend.list\";s:1:\"0\";s:17:\"post|backend.list\";s:1:\"0\";s:24:\"post|backend.list_others\";s:1:\"0\";s:16:\"post|backend.add\";s:1:\"1\";s:24:\"post|backend.edit_others\";s:1:\"0\";s:19:\"post|backend.delete\";s:1:\"0\";s:26:\"post|backend.delete_others\";s:1:\"0\";s:20:\"post|backend.publish\";s:1:\"0\";s:27:\"post|backend.publish_others\";s:1:\"0\";s:17:\"term|backend.list\";s:1:\"0\";s:17:\"term|backend.edit\";s:1:\"0\";s:13:\"post|api.list\";s:1:\"0\";s:20:\"post|api.list_others\";s:1:\"0\";s:20:\"post|api.read_others\";s:1:\"0\";s:12:\"post|api.add\";s:1:\"1\";s:13:\"post|api.edit\";s:1:\"0\";s:20:\"post|api.edit_others\";s:1:\"0\";s:15:\"post|api.delete\";s:1:\"0\";s:22:\"post|api.delete_others\";s:1:\"0\";s:15:\"post|api.teaser\";s:11:\"Not allowed\";s:14:\"post|api.limit\";s:1:\"0\";s:29:\"post|api.access_counter_limit\";s:2:\"11\";s:23:\"post|api.access_counter\";s:1:\"0\";s:16:\"post|api.comment\";s:1:\"0\";s:17:\"post|api.location\";s:26:\"callback|Callback::trigger\";s:17:\"post|api.redirect\";s:1:\"0\";s:17:\"post|api.password\";s:7:\"1234567\";s:18:\"post|api.protected\";s:1:\"0\";s:24:\"post|api.expire_datetime\";s:20:\"11/07/2019, 10:39 am\";s:15:\"post|api.expire\";s:1:\"0\";s:13:\"term|api.list\";s:1:\"0\";s:13:\"term|api.edit\";s:1:\"0\";}','yes');

UNLOCK TABLES;

# ------------------------------------------------------------
# Dump of table wp_postmeta
# ------------------------------------------------------------

LOCK TABLES `wp_postmeta` WRITE;

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`)
VALUES
	(1,'aam-post-access-user1','a:14:{s:13:\"frontend.read\";s:1:\"1\";s:12:\"backend.edit\";s:1:\"1\";s:8:\"api.read\";s:1:\"1\";s:16:\"frontend.comment\";s:1:\"1\";s:11:\"backend.add\";s:1:\"1\";s:10:\"api.delete\";s:1:\"1\";s:13:\"frontend.list\";s:1:\"1\";s:14:\"backend.delete\";s:1:\"1\";s:8:\"api.edit\";s:1:\"1\";s:17:\"frontend.redirect\";s:1:\"1\";s:17:\"frontend.location\";s:11:\"url|303|303\";s:15:\"backend.publish\";s:1:\"1\";s:13:\"api.protected\";s:1:\"1\";s:12:\"api.password\";s:6:\"123456\";}'),
	(1,'aam-post-access-visitor','a:6:{s:13:\"frontend.read\";s:1:\"1\";s:8:\"api.read\";s:1:\"1\";s:13:\"frontend.list\";s:1:\"1\";s:8:\"api.list\";s:1:\"1\";s:16:\"frontend.comment\";s:1:\"1\";s:11:\"api.comment\";s:1:\"1\";}'),
	(1,'aam-post-access-default','a:7:{s:13:\"frontend.read\";s:1:\"1\";s:11:\"backend.add\";s:1:\"1\";s:8:\"api.read\";s:1:\"1\";s:13:\"frontend.list\";s:1:\"1\";s:12:\"backend.edit\";s:1:\"1\";s:8:\"api.list\";s:1:\"1\";s:16:\"frontend.comment\";s:1:\"1\";}'),
	(1,'aam-post-access-roleadministrator','a:44:{s:13:\"frontend.read\";s:1:\"1\";s:12:\"backend.edit\";s:1:\"1\";s:8:\"api.read\";s:1:\"1\";s:13:\"frontend.list\";s:1:\"1\";s:20:\"frontend.list_others\";s:1:\"1\";s:20:\"frontend.read_others\";s:1:\"1\";s:15:\"frontend.teaser\";s:27:\"You are not allowed to read\";s:14:\"frontend.limit\";s:1:\"1\";s:29:\"frontend.access_counter_limit\";s:2:\"10\";s:23:\"frontend.access_counter\";s:1:\"1\";s:16:\"frontend.comment\";s:1:\"1\";s:17:\"frontend.location\";s:11:\"url|302|302\";s:17:\"frontend.redirect\";s:1:\"1\";s:17:\"frontend.password\";s:6:\"123456\";s:18:\"frontend.protected\";s:1:\"1\";s:24:\"frontend.expire_datetime\";s:20:\"10/28/2019, 10:37 am\";s:15:\"frontend.expire\";s:1:\"1\";s:12:\"backend.list\";s:1:\"1\";s:19:\"backend.list_others\";s:1:\"1\";s:11:\"backend.add\";s:1:\"1\";s:19:\"backend.edit_others\";s:1:\"1\";s:14:\"backend.delete\";s:1:\"1\";s:21:\"backend.delete_others\";s:1:\"1\";s:15:\"backend.publish\";s:1:\"1\";s:22:\"backend.publish_others\";s:1:\"1\";s:8:\"api.list\";s:1:\"1\";s:15:\"api.list_others\";s:1:\"1\";s:15:\"api.read_others\";s:1:\"1\";s:7:\"api.add\";s:1:\"1\";s:8:\"api.edit\";s:1:\"1\";s:15:\"api.edit_others\";s:1:\"1\";s:10:\"api.delete\";s:1:\"1\";s:17:\"api.delete_others\";s:1:\"1\";s:10:\"api.teaser\";s:11:\"Not allowed\";s:9:\"api.limit\";s:1:\"1\";s:24:\"api.access_counter_limit\";s:2:\"11\";s:18:\"api.access_counter\";s:1:\"1\";s:11:\"api.comment\";s:1:\"1\";s:12:\"api.location\";s:26:\"callback|Callback::trigger\";s:12:\"api.redirect\";s:1:\"0\";s:12:\"api.password\";s:7:\"1234567\";s:13:\"api.protected\";s:1:\"1\";s:19:\"api.expire_datetime\";s:20:\"11/07/2019, 10:39 am\";s:10:\"api.expire\";s:1:\"1\";}');

UNLOCK TABLES;

# ------------------------------------------------------------
# Dump of table wp_usermeta
# ------------------------------------------------------------

LOCK TABLES `wp_usermeta` WRITE;

INSERT INTO `wp_usermeta` (`user_id`, `meta_key`, `meta_value`)
VALUES
	(1,'wp_aam_menu','a:10:{s:15:\"menu-upload.php\";s:1:\"1\";i:943136475;s:1:\"1\";s:10:\"upload.php\";s:1:\"1\";i:3218358587;s:1:\"1\";s:13:\"media-new.php\";s:1:\"1\";i:867269274;s:1:\"1\";s:11:\"widgets.php\";s:1:\"1\";i:1421817448;s:1:\"1\";s:31:\"edit-tags.php?taxonomy=post_tag\";s:1:\"1\";i:2463043848;s:1:\"1\";}'),
	(1,'wp_aam_toolbar','a:2:{s:13:\"documentation\";s:1:\"1\";s:5:\"wporg\";s:1:\"1\";}'),
	(1,'wp_aam_metabox','a:12:{s:23:\"widgets|WP_Widget_Pages\";s:1:\"1\";i:1287392057;s:1:\"1\";s:29:\"dashboard|dashboard_right_now\";s:1:\"1\";i:3777372316;s:1:\"1\";s:21:\"post|tagsdiv-post_tag\";s:1:\"1\";i:2077369295;s:1:\"1\";s:24:\"widgets|WP_Widget_Search\";s:1:\"1\";i:3421904626;s:1:\"1\";s:28:\"dashboard|dashboard_activity\";s:1:\"1\";i:128113210;s:1:\"1\";s:18:\"post|trackbacksdiv\";s:1:\"1\";i:2181101135;s:1:\"1\";}'),
	(1,'wp_aam_type_post','a:12:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"1\";s:17:\"post|backend.edit\";s:1:\"1\";s:19:\"term|backend.delete\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:15:\"term|api.delete\";s:1:\"1\";s:21:\"post|frontend.comment\";s:1:\"1\";s:18:\"term|frontend.list\";s:1:\"1\";s:16:\"post|backend.add\";s:1:\"1\";s:17:\"term|backend.edit\";s:1:\"1\";s:15:\"post|api.delete\";s:1:\"1\";s:13:\"term|api.edit\";s:1:\"1\";}'),
	(1,'wp_aam_term_1|category','a:17:{s:18:\"post|frontend.read\";s:1:\"1\";s:20:\"term|frontend.browse\";s:1:\"0\";s:17:\"post|backend.edit\";s:1:\"1\";s:19:\"term|backend.delete\";s:1:\"1\";s:13:\"post|api.read\";s:1:\"1\";s:15:\"term|api.delete\";s:1:\"1\";s:21:\"post|frontend.comment\";s:1:\"1\";s:18:\"term|frontend.list\";s:1:\"1\";s:16:\"post|backend.add\";s:1:\"1\";s:17:\"term|backend.edit\";s:1:\"1\";s:15:\"post|api.delete\";s:1:\"1\";s:13:\"term|api.edit\";s:1:\"1\";s:18:\"post|frontend.list\";s:1:\"1\";s:17:\"term|backend.list\";s:1:\"1\";s:19:\"post|backend.delete\";s:1:\"1\";s:13:\"term|api.list\";s:1:\"1\";s:13:\"post|api.edit\";s:1:\"1\";}'),
	(1,'wp_aam_redirect','a:6:{s:22:\"frontend.redirect.type\";s:3:\"url\";s:22:\"frontend.redirect.page\";s:1:\"2\";s:21:\"backend.redirect.type\";s:8:\"callback\";s:24:\"backend.redirect.message\";s:16:\"Access is denied\";s:21:\"frontend.redirect.url\";s:18:\"https://google.com\";s:25:\"backend.redirect.callback\";s:13:\"Test::another\";}'),
	(1,'wp_aam_loginredirect','a:3:{s:19:\"login.redirect.type\";s:4:\"page\";s:18:\"login.redirect.url\";s:18:\"https://google.com\";s:19:\"login.redirect.page\";s:1:\"2\";}'),
	(1,'wp_aam_logoutredirect','a:3:{s:20:\"logout.redirect.type\";s:4:\"page\";s:24:\"logout.redirect.callback\";s:11:\"test::hello\";s:20:\"logout.redirect.page\";s:1:\"2\";}'),
	(1,'wp_aam_route','a:2:{s:23:\"restful|/oembed/1.0|get\";s:1:\"1\";s:29:\"restful|/oembed/1.0/embed|get\";s:1:\"1\";}'),
	(1,'wp_aam_uri','a:2:{s:13:\"5db5a46c9dc3e\";a:4:{s:3:\"uri\";s:11:\"/*/category\";s:4:\"type\";s:4:\"page\";s:6:\"action\";s:1:\"2\";s:4:\"code\";s:3:\"302\";}s:13:\"5db5a619ac5f9\";a:4:{s:3:\"uri\";s:12:\"/sample-page\";s:4:\"type\";s:7:\"message\";s:6:\"action\";s:2:\"No\";s:4:\"code\";s:0:\"\";}}'),
	(1,'aam-jwt','eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NzIxODU2NDYsImlzcyI6Imh0dHA6XC9cL21pZ3JhdGlvbi5hYW0iLCJleHAiOiIxMVwvMDlcLzIwMTksIDEwOjEzIC0wNTAwIiwianRpIjoiYTQ2M2E2ZjktZDczZC00ZTdiLTk4MWMtNmY2ZDFiYTFjMTJlIiwidXNlcklkIjoxLCJyZXZvY2FibGUiOnRydWUsInJlZnJlc2hhYmxlIjp0cnVlfQ.eMZzyYqq-essJlUDRysdeEYZEqax0eF0gP_ouY58DMA'),
	(1,'wp_aam_taxonomy_post|category','a:1:{s:7:\"default\";s:1:\"3\";}');

UNLOCK TABLES;