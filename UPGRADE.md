# Upgrade 5.x

## X.Y.Z

### (BREAKING): Removed language fallback using SiteConfig ISO and HREF

DeeplTranslate only supports a narrowed down list of selected languages, which
is only a subset of TYPO3 supported languages and the reason why a dedicated
option `DeeplTranslate Language` is provided on the SiteConfig language level.

As a left over from the original `Proof-of-Concept` phase and the first version
iteration a fallback to `HREF` and `ISO Locale` has been in place trying to get
some kind of fallback. That turned out not to be that reliable and becomes more
unreliable and invalid with planned upcoming features.

Even with the fallback in place it has been recommended to specify the deepl
translate language manually for a long time and the fallback is now removed
in favour of explicit, manual configuration.

> [!IMPORTANT]
> This is technically breaking and SiteConfiguration needs to be checked and
> the language set manually to mitigate this issue. This can be done already
> since quite a lot version.

## 5.0.3

## 5.0.2

## 5.0.1

## 5.0.0
