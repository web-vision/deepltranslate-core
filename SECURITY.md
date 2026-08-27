# Security Policy

This product ("web-vision/deepltranslate-core") is developed and maintained by web-vision GmbH.
As a manufacturer of a product with digital elements under the EU Cyber
Resilience Act (Regulation (EU) 2024/2847), we are committed to identifying,
assessing, and remediating security vulnerabilities in this product in a
timely manner, and to coordinating responsibly with security researchers.

## Supported Versions

Security updates are provided for the following versions. Versions marked
unsupported no longer receive security fixes; please upgrade before
reporting an issue against them.

| Version | Supported          |
| ------- | ------------------ |
| 6.x     | :white_check_mark: |
| 5.x     | :white_check_mark: |
| < 5.0   | :x:                |

Planned end of support for this product: **30 June 2029 (end of regular TYPO3 14 LTS support)**.

## Reporting a Vulnerability

Please report suspected security vulnerabilities privately — **do not**
open a public GitHub/GitLab issue.

- **Report here:** https://security.web-vision.de (our secure incident report form)
- **Please include:** affected version(s), a description of the issue,
  steps to reproduce or a proof of concept, and the potential impact.

### What to expect

| Step                                | Timeframe                                                              |
| ----------------------------------- | ---------------------------------------------------------------------- |
| Acknowledgement of your report      | within 1 business day (typically much faster)                          |
| Status updates                      | at least every 7 days until resolved                                   |
| Fix / mitigation, based on severity | Critical/High: as fast as possible; Medium/Low: next scheduled release |

We coordinate the disclosure timeline with the reporter and aim for a
resolution before any public disclosure. If you'd like credit for your
finding, let us know how you'd like to be named; we're also happy to keep
your report confidential if you prefer.

## Safe Harbor

We consider security research conducted in good faith, in accordance with
this policy, to be authorized. We will not pursue legal action against
researchers who:

- make a genuine effort to avoid privacy violations, data destruction, and
  service interruption during their research,
- report a vulnerability promptly and do not exploit it beyond what is
  necessary to demonstrate the issue,
- do not publicly disclose the vulnerability before we have had a
  reasonable opportunity to address it (see timeframes above).

## Scope

In scope: the source code, released versions, and official distribution
channels of "web-vision/deepltranslate-core" (e.g. TER / Packagist).

Out of scope: third-party dependencies (please report those upstream, but
feel free to let us know so we can track and update them), and
vulnerabilities in the host application (TYPO3 core) itself
unless directly caused by this extension.

## Coordinator

Vulnerability reports for this product are handled by the
Vulnerability Coordinator team at web-vision GmbH,
under the oversight of our GRC/CISO function.
