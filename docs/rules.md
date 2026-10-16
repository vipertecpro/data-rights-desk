# Rules map

This file maps each feature of Data Rights Desk to the provision it helps an app meet. It is not legal advice. Check your own duties with someone who knows your business.

- **The Act** is the Digital Personal Data Protection Act, 2023 (Act No. 22 of 2023).
- **The Rules** are the Digital Personal Data Protection Rules, 2025 (G.S.R. 846(E)).
- Quotes are copied from plain-text extracts of the gazette PDFs. Line breaks and the spacing around hyphens are normalised, and nothing else is changed.
- The test column gives the start of the test names in `tests/`. Every duty row has at least one test whose name starts with that text.
- **unverified** marks a point that could not be settled from the gazette text. Each one is explained under "Open points".

## When the duties start

| Group | Provisions | Starts |
| --- | --- | --- |
| On publication | Section 1(2), section 2, sections 18 to 26 (the Board) and a few others; Rules 1, 2 and 17 to 21 | 13 Nov 2025 |
| One year | Section 6(9) (Consent Manager registration); Rule 4 and the First Schedule | 13 Nov 2026 |
| Eighteen months | Sections 3 to 5, 6(1) to 6(8), 6(10), sections 7 to 17 and others; Rules 3, 5 to 16, 22 and 23 | 13 May 2027 |

The Rules say it themselves: "Rules 3, 5 to 16, 22 and 23 shall come into force eighteen months after the date of publication" (Rule 1(4), G.S.R. 846(E)). Rule 1(3) says the same for Rule 4 with "one year".

The dates for the Act's sections come from G.S.R. 843(E), the commencement notification (13 Nov 2025). That notification is not quoted here: check the section dates against the gazette. Plan for **13 May 2027**. Some sources count from 14 Nov 2025 and give the 14th (**unverified**, see "Open points").

As of 16 October 2026, when this file was written, no duty of an ordinary business app (a Data Fiduciary) is in force. The provisions about the Board started on 13 Nov 2025, and Consent Manager registration (Rule 4 and section 6(9)) starts on 13 Nov 2026. Neither is a duty of an ordinary business, and this package does not act as a Consent Manager.

## 1. Notice and language

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Consent cannot be recorded until a notice is published in that language | s.5(1) | "accompanied or preceded by a notice given by the Data Fiduciary to the Data Principal" | Act No. 22 of 2023 | 13 May 2027 | `s.5(1):` | `give()` throws `NoticeMissing` when no notice is published for the language. |
| The notice stands alone | Rule 3(a) | "understandable independently of any other information" | G.S.R. 846(E) | 13 May 2027 | `Rule 3:` | The notice is built as standalone Markdown from your config and the language files. Every published version is kept and hashed, and past versions stay readable. A new version is published only when the text changed or a language was added. |
| The notice itemises the data and states each purpose and use | Rule 3(b)(i) and (ii) | "specific description of the goods or services to be provided or uses to be enabled" | G.S.R. 846(E) | 13 May 2027 | `Rule 3:` | Per purpose: title, description, goods or services, itemised data, basis, and the withdrawal effect when you set one. |
| The notice links to withdrawal, to the rights page and to the Board | Rule 3(c)(i) to (iii) | "with the ease of doing so being comparable to that with which such consent was given" | G.S.R. 846(E) | 13 May 2027 | `Rule 3:` | The Board URL is empty by default, so the notice describes the complaint route without a link. **unverified**: the Board had no published complaint channel on 16 Oct 2026. |
| People who consented before get the notice | s.5(2) | "as soon as it is reasonably practicable" | Act No. 22 of 2023 | 13 May 2027 | `s.5(2):` | `data-rights:notify-existing` sends the current version once per person and records it. The Act sets no number of days. `--before` defaults to 2027-05-13. |
| The notice is offered in English or an Eighth Schedule language | s.5(3), s.6(3) | "in English or any language specified in the Eighth Schedule to the Constitution" | Act No. 22 of 2023 | 13 May 2027 | `s.5(3):` | English is ready, Hindi is under review, the other 21 languages need translation (see `docs/translations.md`). A language with the status `needed` cannot be offered. |
| The consent request shows who to contact | s.6(3) | "providing the contact details of a Data Protection Officer, where applicable" | Act No. 22 of 2023 | 13 May 2027 | `s.5(3):` | The contact block is on the consent page. The Act also allows another person the business authorises. |

## 2. Consent and withdrawal

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| A purpose is allowed only on a lawful ground | s.4(1) | "only in accordance with the provisions of this Act and for a lawful purpose" | Act No. 22 of 2023 | 13 May 2027 | `s.7:` | `DataRights::allows()` answers per purpose. Choosing the purpose and its ground is yours, and the package does not judge whether a purpose is lawful. |
| Consent is per purpose and needs an action by the person | s.6(1) | "free, specific, informed, unconditional and unambiguous with a clear affirmative action" | Act No. 22 of 2023 | 13 May 2027 | `s.6(1):` | Consent is recorded only from the person's own request. Nothing is pre-ticked and there is no accept-all control. |
| Withdrawing is as easy as giving | s.6(4) | "with the ease of doing so being comparable to the ease with which such consent was given" | Act No. 22 of 2023 | 13 May 2027 | `s.6(4):` | One click, on the same page, in the same shape as giving. |
| A withdrawal reaches your code | s.6(6) | "cease and cause its Data Processors to cease processing the personal data of such Data Principal" | Act No. 22 of 2023 | 13 May 2027 | `s.6(6):` | The package fires `ConsentWithdrawn`. Stopping the processing, and telling your processors, is your app's job. |
| Legitimate uses | s.7 | "voluntarily provided her personal data to the Data Fiduciary" | Act No. 22 of 2023 | 13 May 2027 | `s.7:` | Clauses (b) to (i) are always allowed. Clause (a) is allowed until the person says she does not want the use. |
| Consent given through a Consent Manager is recorded | s.6(7) to (9), Rule 4 | "give, manage, review or withdraw her consent to the Data Fiduciary through a Consent Manager" | Act No. 22 of 2023 | 13 May 2027 for the Data Fiduciary side; 13 Nov 2026 for Rule 4 | `s.6(7):` | The package records such consent (channel `consent_manager`, the manager and a reference) and counts it in `allows()`. It does not talk to any Consent Manager. **unverified**: no technical standard is published. |
| Proof of notice and consent | s.6(10) | "obliged to prove that a notice was given by her to the Data Principal and consent was given" | Act No. 22 of 2023 | 13 May 2027 | `s.6(10):` | The ledger is append-only. Each row names the notice version and its hash. `data-rights:proof` writes every ledger row, the text and hash of each notice it names, and the deliveries. |

## 3. Rights of the Data Principal

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| A summary of the person's data | s.11(1)(a) | "a summary of personal data which is being processed by such Data Fiduciary" | Act No. 22 of 2023 | 13 May 2027 | `s.11:` | Built from your Exporters, the processing log of the last `keep_days`, the consents, and the processors register. |
| Who the data was shared with | s.11(1)(b), s.11(2) | "any other Data Fiduciary authorised by law to obtain such personal data" | Act No. 22 of 2023 | 13 May 2027 | `s.11:` | The summary lists active processors for the person's purposes. A processor marked `hidden_from_access_summary` is left out, for the sharing that s.11(2) excludes. |
| Correction, completion, updating | s.12(2) | "correct the inaccurate or misleading personal data" | Act No. 22 of 2023 | 13 May 2027 | `s.12(2):` | A request with a status, an event log and a reply. Changing the data itself is done in your app. |
| Erasure on request | s.12(3) | "shall erase her personal data unless retention of the same is necessary for the specified purpose or for compliance with any law" | Act No. 22 of 2023 | 13 May 2027 | `s.12(3):` | Runs every Eraser, stores a report of counts and reasons, and tells the person. A legal hold stops it and the report says so. |
| Grievance redressal | s.8(10), s.13 | "establish an effective mechanism to redress the grievances of Data Principals" | Act No. 22 of 2023 | 13 May 2027 | `Rule 14(3):` | A grievance is a request type with its own clock. |
| How to ask, and what identifies you | Rule 14(1) | "which may be required to identify her under its terms of service" | G.S.R. 846(E) | 13 May 2027 | `Rule 14(1):` | The public rights page shows how to ask and the identifier, without sign-in. Identity is checked by the signed-in session, or by a person who records the method on the dashboard. Erasure waits for that check. |
| Reply within a set time | Rule 14(3) | "within a reasonable period not exceeding ninety days" | G.S.R. 846(E) | 13 May 2027 | `Rule 14(3):` | The package's own choice: 90 days is the ceiling for every request type. The Rules set 90 days for grievances only. See "Where the package chose". |
| Nominate someone | s.14(1), Rule 14(4) | "nominate one or more individuals" | G.S.R. 846(E) | 13 May 2027 | `s.14:` | Nominee fields are encrypted. They are erased with the person. Removing a nomination marks it revoked, and the row is deleted when the person is erased. |

## 4. Processors, security and breaches

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Processor register | s.8(2) | "only under a valid contract" | Act No. 22 of 2023 | 13 May 2027 | `s.8(2):` | The checker warns about an active processor with no contract reference or no security clause. |
| Security clause in the processor contract | Rule 6(1)(f) | "appropriate provision in the contract entered into between such Data Fiduciary and such a Data Processor" | G.S.R. 846(E) | 13 May 2027 | `s.8(2):` | The register records whether the contract has a security clause. |
| Encrypted storage of the package's own free text | s.8(5), Rule 6(1)(a) | "securing of personal data through encryption, obfuscation, masking or the use of virtual tokens" | G.S.R. 846(E) | 13 May 2027 | `Rule 6:` | Free-text fields in the package's tables use Laravel's encrypted cast. Safeguards for the rest of your app (access control, logs, backups, your database) are yours. |
| Tell each affected person | s.8(6), Rule 7(1) | "in a concise, clear and plain manner and without delay" | G.S.R. 846(E) | 13 May 2027 | `Rule 7(1):` | A message with items (a) to (e), sent once per person, with the time recorded. The Rules give no number of hours for this one. |
| Alert the Board | Rule 7(2)(a) | "without delay, a description of the breach, including its nature, extent, timing and location of occurrence and the likely impact" | G.S.R. 846(E) | 13 May 2027 | `Rule 7(2):` | The package drafts the alert. You check it and send it. Nothing is sent to the Board by the package. |
| Report to the Board in 72 hours | Rule 7(2)(b) | "within seventy-two hours of becoming aware of the breach" | G.S.R. 846(E) | 13 May 2027 | `Rule 7(2):` | Due time is the time you became aware plus 72 hours. A longer period the Board allows in writing can be recorded. Overdue breaches show on the dashboard and fail the checker. The draft covers items (i) to (vi). |
| Report on the people told | Rule 7(2)(b)(vi) | "a report regarding the intimations given to affected Data Principals" | G.S.R. 846(E) | 13 May 2027 | `Rule 7(2):` | The report draft counts the people recorded as affected and told. |

## 5. Retention and erasure

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Keep logs of processing for a year | Rule 8(3), Rule 6(1)(e) | "a minimum period of one year from the date of such processing" | G.S.R. 846(E) | 13 May 2027 | `Rule 8(3):` | `keep_days` is never below 365. The keeper deletes processing logs, old closed requests and the package records of people erased long ago. Models using `HasRetention` are erased after `max(model days, keep_days)`. |
| Erase when consent is withdrawn | s.8(7)(a) | "erase personal data, upon the Data Principal withdrawing her consent" | Act No. 22 of 2023 | 13 May 2027 | `s.12(3):` | Erasure runs through your Erasers. Deciding that a purpose is served is yours. |
| Make processors erase too | s.8(7)(b) | "cause its Data Processor to erase any personal data that was made available by the Data Fiduciary" | Act No. 22 of 2023 | 13 May 2027 | `s.12(3):` | The package fires `PersonalDataErased`. Telling your processors is your app's job. |
| Keep what a law needs | Rule 8(1), s.8(7) | "unless its retention is necessary for compliance with any law" | G.S.R. 846(E) | 13 May 2027 | `Rule 8(3):` | An Eraser returns `retained()` with a reason. A legal hold keeps a record or a person from the keeper and from erasure. |
| Erase after three years of inactivity (three classes only) | Rule 8(1), Third Schedule | "or the commencement of the Digital Personal Data Protection Rules, 2025, whichever is latest" | G.S.R. 846(E) | 13 May 2027 | `Rule 8(1):` | Off by default. Only for e-commerce entities with at least two crore registered users in India, online gaming intermediaries with at least fifty lakh, and social media intermediaries with at least two crore. The package cannot tell whether your business is in one of these classes: you turn it on. **unverified**: which commencement the clock counts from. `counting_from` defaults to 2025-11-13. |
| Warn 48 hours before erasure | Rule 8(2) | "At least forty-eight hours before completion of the time period for erasure of personal data" | G.S.R. 846(E) | 13 May 2027 | `Rule 8(2):` | `warning_hours` is never below 48. Signing in or making a request clears the warning. |
| Keep account access and stored-value tokens | Third Schedule, exceptions (a) and (b) | "Enabling the Data Principal to access her user account" | G.S.R. 846(E) | 13 May 2027 | `Rule 8(1):` | On inactivity erasure the Eraser is told through `keepsAccountAccess()` to keep what the person needs to sign in and any stored-value virtual token. |

## 6. Contact

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Publish a business contact | s.8(9), Rule 9 | "prominently publish on its website or app" | G.S.R. 846(E) | 13 May 2027 | `Rule 9:` | The rights page shows the contact block. |
| Put it in every reply | Rule 9 | "mention in every response to a communication for the exercise of the rights of a Data Principal" | G.S.R. 846(E) | 13 May 2027 | `Rule 9:` | Every notice and every request email ends with the contact block. The checker fails when neither an email nor a phone is set. |

## 7. Children and persons with disability

| Feature | Section or rule | Quote from the gazette | Reference | Starts | Tests | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Parent's or lawful guardian's consent first | s.9(1) | "obtain verifiable consent of the parent of such child or the lawful guardian, as the case may be" | Act No. 22 of 2023 | 13 May 2027 | `s.9(1):` | A declared child is not allowed until an active guardian consent and a consent row given by the parent exist. The default verifier refuses, so your app must supply its own. |
| The S.O. 5458(E) wording | s.9(1) as changed | "child or of a person with disability" | S.O. 5458(E), 5 Oct 2026 | 6 Oct 2026 (the order); applies to sections starting 13 May 2027 | `s.9(1):` | An editorial change to the words of s.9(1). The package text uses the amended words. |
| Check the parent is an adult | Rule 10(1) | "for checking that the individual identifying herself as the parent is an adult" | G.S.R. 846(E) | 13 May 2027 | `s.9(1):` | The package records the method (`reliable_details_held`, `details_provided` or `virtual_token`). Doing the check is your verifier's job. |
| Who counts as an adult | Rule 10(2)(a) | "who has completed the age of eighteen years" | G.S.R. 846(E) | 13 May 2027 | `Minimisation:` | The person states adult or under 18. Only that answer is stored, never a birth date. The package does not verify age. |
| Lawful guardian of a person with disability | Rule 11(1) | "appointed by a court of law, or by a designated authority or by a local level committee" | G.S.R. 846(E) | 13 May 2027 | `Rule 11:` | A person marked `has_lawful_guardian` needs a lawful-guardian consent recorded with a Rule 11 method. |
| No tracking of children | s.9(3) | "shall not undertake tracking or behavioural monitoring of children or targeted advertising directed at children" | Act No. 22 of 2023 | 13 May 2027 | `s.9(3):` | A purpose marked `tracking` stays denied for a child even with a parent's consent. `@dataRightsTracking` hides tracking code. |
| Exempt classes and purposes | Rule 12, Fourth Schedule | "shall not be applicable to processing of personal data of a child by such class of Data Fiduciaries" | G.S.R. 846(E) | 13 May 2027 | `Rule 12:` | A purpose with a Fourth Schedule key skips the checks of s.9(1) and s.9(3). You declare that the Schedule's condition is met. The package cannot check it. |

## 8. Behaviour that is the package's own

These rows are not duties of the Act. They are choices that keep the package itself safe and small.

| Feature | Tests | Notes |
| --- | --- | --- |
| IP address and user agent are not stored unless you turn them on | `Minimisation:` | Only the age status is stored. The package writes no personal data to your logs. |
| The dashboard and its JSON need a signed-in user who passes the gate | `Security:` | A person cannot see another person's request, download or nomination. Request creation is limited to six a minute. |
| The checker looks for gaps | `Checker:` | It reports what the package can see. It cannot tell whether your app meets the Act. |
| No outbound network calls | (test case setup) | The suite runs with stray HTTP requests prevented. |

## Not covered in v1.0.0

| Topic | Provision | Quote from the gazette | Notes |
| --- | --- | --- | --- |
| Significant Data Fiduciary duties | s.10, Rule 13 | "once in every period of twelve months" | Impact assessment, audit, an India-based Data Protection Officer. Not built. **unverified**: no Data Fiduciary was found notified as one. |
| Acting as a Consent Manager | s.6(9), Rule 4, First Schedule | "data protection standards and assurance framework as may be published by the Board on its website from time to time" | Not built. **unverified**: no such standard was found published. |
| Transfers outside India | s.16(1), Rule 15 | "restrict the transfer of personal data by a Data Fiduciary for processing to such country or territory outside India" | Not built. **unverified**: no country was found notified. |
| No processing that harms a child's well-being | s.9(2) | "likely to cause any detrimental effect on the well-being of a child" | The package cannot judge what is harmful. That is yours. |
| Government requests for information | Rule 23 | "within the specified period" | Not built. |
| Sending anything to the Board | s.8(6), Rule 7(2) | "shall intimate to the Board" | The package drafts. The business sends. |

## Where the package chose

The Rules set no number for these. The package made its own choice and says so.

| Setting | Default | Why |
| --- | --- | --- |
| `requests.response_days` | 90, and never above 90 | Rule 14(3) sets 90 days for grievances only. The package uses 90 days as the ceiling for every request type. |
| `requests.download_days` | 30 | How long an access summary stays downloadable after the request is completed. |
| `retention.keep_days` | 365, and never below 365 | The minimum of Rule 8(3) and Rule 6(1)(e). A higher number is yours to choose. |
| `retention.inactivity.counting_from` | 2025-11-13 | The earlier of the two possible commencement dates, so erasure is no later than either reading needs. It can erase sooner than the later reading requires. |
| `retention.inactivity.warning_hours` | 48, and never below 48 | The minimum of Rule 8(2). |
| `notify-existing --before` | 2027-05-13 | People created before the duties start. |
| Request creation limit | 6 a minute per user | Keeps one account from flooding the desk. |

## Open points

| Point | What is known | Status |
| --- | --- | --- |
| The 13th or the 14th | The gazette is dated 13 Nov 2025. Other records give 14 Nov 2025. So the later dates could be 14 Nov 2026 and 14 May 2027. | **unverified**. Plan for the 13th. |
| The clock of the Third Schedule | "commencement of the Digital Personal Data Protection Rules, 2025" could mean 13 Nov 2025 or 13 May 2027. | **unverified**. The package defaults to the earlier date and lets you change it. |
| A Board complaint channel | No published means of complaint was found on 16 Oct 2026. | **unverified**. `board.url` is empty by default. |
| Consent Manager standards | The Board has published no data protection standards and assurance framework. Sources disagree on when registration opens (13 Nov 2026 or 13 May 2027). | **unverified**. |
| Significant Data Fiduciaries | None notified, as far as could be found. | **unverified**. |
| A 12-month window | A proposal to cut the 18-month window to 12 months was reported in the press. No amending notification was found. | **unverified**. Not gazetted. |
| Restricted countries (s.16) | None notified, as far as could be found. | **unverified**. |

## Changes since the Rules

- **G.S.R. 892(E)**, 10 Dec 2025: a corrigendum. It reworded "of this Gazette" in Rule 1(3) and (4), fixed typing errors and changed "(a) to (f)" to "(a) to (g)" in the notes to the Fourth Schedule. No date moved.
- **S.O. 5458(E)**, 5 Oct 2026: the Digital Personal Data Protection (Removal of Difficulties) Order, 2026. It changed the words of section 9(1) so that it reads "child or of a person with disability", and in section 10(2)(c)(ii) it replaced "audit" with "data audit". Both are editorial. It came into force on its publication in the Gazette (6 Oct 2026).

## Sources

All are read as plain text from the gazette PDFs.

- Digital Personal Data Protection Act, 2023, Act No. 22 of 2023, Gazette of India Extraordinary, Part II, Section 1, No. 25. Assented to and published 11 Aug 2023.
- G.S.R. 843(E), 13 Nov 2025: commencement of the Act, Gazette of India Extraordinary, Part II, Section 3(i), No. 757. Used for the dates of the Act's sections. Not quoted.
- G.S.R. 846(E), 13 Nov 2025: the Digital Personal Data Protection Rules, 2025, Gazette of India Extraordinary, Part II, Section 3(i), No. 760. Some copies are dated 14 Nov 2025.
- G.S.R. 892(E), 10 Dec 2025: corrigendum to G.S.R. 846(E), Gazette of India Extraordinary, Part II, Section 3(i), No. 806.
- S.O. 5458(E), 5 Oct 2026: the Digital Personal Data Protection (Removal of Difficulties) Order, 2026, Gazette of India Extraordinary, Part II, Section 3(ii), No. 5248, published 6 Oct 2026.

Data Rights Desk is an independent open-source project. It is not affiliated with or endorsed by Laravel, the Government of India, MeitY or the Data Protection Board of India.
