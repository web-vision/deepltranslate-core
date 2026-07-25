#
# All columns are defined explicitly, because TYPO3 v12 only enriches tables already known from
# `ext_tables.sql` and does not create them from TCA alone.
#
CREATE TABLE tx_testinlinerelations_parent (
    title varchar(255) DEFAULT '' NOT NULL,
    children_declared int(11) DEFAULT '0' NOT NULL,
    children_undeclared int(11) DEFAULT '0' NOT NULL
);

#
# `parentid` of `tx_testinlinerelations_child_declared` is configured as `type => passthrough` in
# TCA, which requires a manual column definition, see EXT:styleguide for the same pattern.
#
CREATE TABLE tx_testinlinerelations_child_declared (
    title varchar(255) DEFAULT '' NOT NULL,
    parentid int(11) DEFAULT '0' NOT NULL
);

#
# `parentid` of `tx_testinlinerelations_child_undeclared` is intentionally *not* configured in TCA
# at all - which is the common real-world setup for a pure `foreign_field` pointer column and the
# exact setup reported in https://github.com/web-vision/deepltranslate-core/issues/503.
#
CREATE TABLE tx_testinlinerelations_child_undeclared (
    title varchar(255) DEFAULT '' NOT NULL,
    parentid int(11) DEFAULT '0' NOT NULL
);
