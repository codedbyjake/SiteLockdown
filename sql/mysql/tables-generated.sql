CREATE TABLE /*_*/sitelockdown_state (
  sld_id TINYINT UNSIGNED NOT NULL,
  sld_active TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sld_activated_by_actor BIGINT UNSIGNED DEFAULT NULL,
  sld_activated_at BINARY(14) DEFAULT NULL,
  sld_reason BLOB DEFAULT NULL,
  PRIMARY KEY(sld_id)
) /*$wgDBTableOptions*/;
