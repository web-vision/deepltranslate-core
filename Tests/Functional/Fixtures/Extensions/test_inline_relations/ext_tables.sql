#
# On TYPO3 v13/v14 the schema analyzer creates the fixture tables and their `type`-based columns
# from TCA alone, so only columns it cannot derive are declared here:
#
#   - `type => passthrough` pointer columns (no auto column is created for them), and
#   - the pointer column of `tx_testinlinerelations_child_undeclared`, which is intentionally not
#     configured in TCA at all - the pure `foreign_field` setup reported in issue #503.
#
# The branch `5` variant of this file additionally declares every table and column explicitly,
# because TYPO3 v12 only enriches tables already known from `ext_tables.sql`.
#

# Pointer column back to `tx_testinlinerelations_contentparent`, declared as `passthrough` in the
# `tt_content` TCA override (mirrors EXT:news `tx_news_related_news`).
CREATE TABLE tt_content (
    tx_testinlinerelations_related int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testinlinerelations_child_declared (
    parentid int(11) DEFAULT '0' NOT NULL,
    parentid_ambiguous int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_testinlinerelations_child_undeclared (
    parentid int(11) DEFAULT '0' NOT NULL
);

# `translationSource` (l10n_source) is a passthrough pointer column, so it is declared explicitly
# (used to reproduce the TYPO3 Core empty-l10n_source lookup defect, forge #110281).
CREATE TABLE tx_testinlinerelations_l10nsource (
    title varchar(255) DEFAULT '' NOT NULL,
    l10n_source int(11) DEFAULT '0' NOT NULL
);
