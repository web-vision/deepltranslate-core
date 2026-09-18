CREATE TABLE pages
(
	tx_wvdeepltranslate_content_not_checked tinyint unsigned DEFAULT '0' NOT NULL,
	tx_wvdeepltranslate_translated_time     int(11) NOT NULL DEFAULT '0',
	tx_wvdeepltranslate_context             text
);
