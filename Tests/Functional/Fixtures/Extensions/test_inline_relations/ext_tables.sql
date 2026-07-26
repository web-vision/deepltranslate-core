#
# All columns are defined explicitly, because TYPO3 v12 only enriches tables already known from
# `ext_tables.sql` and does not create them from TCA alone.
#
CREATE TABLE tx_testinlinerelations_parent (
    title varchar(255) DEFAULT '' NOT NULL,
    children_declared int(11) DEFAULT '0' NOT NULL,
    children_undeclared int(11) DEFAULT '0' NOT NULL,
    children_declared_ambiguous int(11) DEFAULT '0' NOT NULL
);

#
# Parent embedding shared `tt_content` records, modelled on EXT:news `content_elements`.
#
CREATE TABLE tx_testinlinerelations_contentparent (
    title varchar(255) DEFAULT '' NOT NULL,
    content_elements int(11) DEFAULT '0' NOT NULL
);

#
# Pointer column back to `tx_testinlinerelations_contentparent`, declared as `passthrough` in the
# `tt_content` TCA override (mirrors EXT:news `tx_news_related_news`).
#
CREATE TABLE tt_content (
    tx_testinlinerelations_related int(11) DEFAULT '0' NOT NULL
);

#
# `parentid` of `tx_testinlinerelations_child_declared` is configured as `type => passthrough` in
# TCA, which requires a manual column definition, see EXT:styleguide for the same pattern.
# `parentid_ambiguous` is a second such pointer used only to construct an ambiguous relation.
#
CREATE TABLE tx_testinlinerelations_child_declared (
    title varchar(255) DEFAULT '' NOT NULL,
    parentid int(11) DEFAULT '0' NOT NULL,
    parentid_ambiguous int(11) DEFAULT '0' NOT NULL
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

#
# Table declaring a `translationSource` (l10n_source), used to reproduce the TYPO3 Core
# empty-l10n_source lookup defect (forge #110281). `l10n_source` is a `type => passthrough` column
# and is therefore declared explicitly here.
#
CREATE TABLE tx_testinlinerelations_l10nsource (
    title varchar(255) DEFAULT '' NOT NULL,
    l10n_source int(11) DEFAULT '0' NOT NULL
);
