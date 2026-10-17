# Translations

Section 5(3) and section 6(3) of the Digital Personal Data Protection Act, 2023 ask for the notice and every consent request to be offered "in English or any language specified in the Eighth Schedule to the Constitution". This file says which languages the package ships, how they were made, what a fluent reader should check first in each, and how to change or add one.

## What ships

- **English** is ready.
- **All 22 languages of the Eighth Schedule** are there as drafts. I drafted them with AI from the English text. The legal terms follow the Hindi text of the DPDP Rules, 2025 published in the Gazette of India (G.S.R. 846(E)); the table under "Legal terms" lists them with the page and rule they come from. No fluent reader has checked any of them yet, so each has the status `needs_review` and the source `ai-draft` in its `meta.php`. They can be offered, and the checker warns until a language is marked `ready`.
- **The text of your own purposes** (titles, descriptions, the data each uses) is yours to write in each language you offer. The package cannot translate it for you.

The dashboard (the staff screens) is English only. The translations cover what a Data Principal sees: the notice, the rights page, the consent choices, the requests pages and the emails.

Please do not rely on a machine translation of a legal notice as it is. Before you offer a language, ask a fluent speaker to read it (the points under "What to check first" are a good start), fix what they find, and then set its status to `ready`.

## The languages

| Code | Language | Name in the language | Script | Direction | Status |
| --- | --- | --- | --- | --- | --- |
| `hi` | Hindi | हिन्दी | Devanagari | ltr | needs_review (ai-draft) |
| `as` | Assamese | অসমীয়া | Bengali-Assamese | ltr | needs_review (ai-draft) |
| `bn` | Bengali | বাংলা | Bengali | ltr | needs_review (ai-draft) |
| `brx` | Bodo | बड़ो | Devanagari | ltr | needs_review (ai-draft) |
| `doi` | Dogri | डोगरी | Devanagari | ltr | needs_review (ai-draft) |
| `gu` | Gujarati | ગુજરાતી | Gujarati | ltr | needs_review (ai-draft) |
| `kn` | Kannada | ಕನ್ನಡ | Kannada | ltr | needs_review (ai-draft) |
| `ks` | Kashmiri | کٲشُر | Perso-Arabic | rtl | needs_review (ai-draft) |
| `kok` | Konkani | कोंकणी | Devanagari | ltr | needs_review (ai-draft) |
| `mai` | Maithili | मैथिली | Devanagari | ltr | needs_review (ai-draft) |
| `ml` | Malayalam | മലയാളം | Malayalam | ltr | needs_review (ai-draft) |
| `mni` | Manipuri | Manipuri | Meitei Mayek | ltr | needed (by hand) |
| `mr` | Marathi | मराठी | Devanagari | ltr | needs_review (ai-draft) |
| `ne` | Nepali | नेपाली | Devanagari | ltr | needs_review (ai-draft) |
| `or` | Odia | ଓଡ଼ିଆ | Odia | ltr | needs_review (ai-draft) |
| `pa` | Punjabi | Punjabi | Gurmukhi | ltr | needed (by hand) |
| `sa` | Sanskrit | संस्कृतम् | Devanagari | ltr | needs_review (ai-draft) |
| `sat` | Santali | Santali | Ol Chiki | ltr | needed (by hand) |
| `sd` | Sindhi | سنڌي | Perso-Arabic | rtl | needs_review (ai-draft) |
| `ta` | Tamil | தமிழ் | Tamil | ltr | needs_review (ai-draft) |
| `te` | Telugu | తెలుగు | Telugu | ltr | needs_review (ai-draft) |
| `ur` | Urdu | اردو | Perso-Arabic (Nastaliq) | rtl | needs_review (ai-draft) |

English (`en`) is not on the Eighth Schedule. It is always available as the default language, and its status is `ready`.

Kashmiri, Sindhi and Manipuri are written in more than one script. The drafts use Perso-Arabic for Kashmiri and Sindhi, and Meitei Mayek for Manipuri, the script Manipur uses officially. Santali is in Ol Chiki. Some phones and computers have no font for Ol Chiki or Meitei Mayek; check the pages on your users' devices, and add a web font in your own layout if a script shows as empty boxes.

## How a language is offered

Three things decide it:

1. **The status** in `lang/{code}/meta.php`: `ready`, `needs_review` or `needed`. Only `needed` blocks a language.
2. **The `locales` list** in `config/data-rights.php`. Only the codes listed there are offered. The default is `['en', 'hi']`.
3. **The text of your purposes.** Every purpose needs a title, a description, a description of the goods or services, the list of data and (when set) the withdrawal effect in each offered language.

`php artisan data-rights:notice` refuses to publish, and names what is missing, when a listed language has the status `needed` or when a purpose has no text in an offered language. `php artisan data-rights:check` reports a listed `needed` language as an error, warns when Hindi is not offered, and warns while an offered language has the status `needs_review`.

If you keep the default `locales` and write only English text for your own purposes, the notice command stops at the Hindi text. Either write the Hindi text for your purposes, or set `'locales' => ['en']`.

## Change a language, or add another

1. Publish the language files into your app:

   ```
   php artisan vendor:publish --tag=data-rights-lang
   ```

   This copies every language folder to `lang/vendor/data-rights/`.

2. In `lang/vendor/data-rights/{code}/`, edit `notice.php`, `public.php` and `mail.php`. For a language that is not here, start from copies of the English files.
3. Do not change the keys. Keep every `:placeholder` (for example `:business`, `:url`, `:date`, `:days`); you may move it within the sentence. Keep the quotation marks around the button names in the sentences that name them.
4. In `meta.php`, set `status` to `ready` once a fluent reader has checked the text, and remove `source`. For a new language, also set `name` (its own name for itself) and `direction` (`rtl` or `ltr`).
5. Add the code to `locales` in `config/data-rights.php`, and add the language to the text of each of your purposes.
6. Run `php artisan data-rights:notice`, then `php artisan data-rights:check`.

People switch language with `?lang={code}` on the notice, rights and preferences pages. The language switcher lists only the offered languages, by their own names.

## Legal terms

These are the terms the Hindi text of the DPDP Rules, 2025 (G.S.R. 846(E), Gazette of India, 13 Nov 2025) uses. The Hindi strings use them, and the drafts in the other languages were written with them as the meaning to keep. Where the Rules do not use a term, the Hindi draft chose one and a fluent reader should confirm it.

| English | Hindi, as in the gazette | Where |
| --- | --- | --- |
| Digital Personal Data Protection Act, 2023 | डिजिटल वैयक्तिक डेटा संरक्षण अधिनियम, 2023 | p. 1, Preamble (G.S.R. 846(अ)); also rule 2(1)(क) on p.2 |
| Digital Personal Data Protection Rules, 2025 | डिजिटल वैयक्तिक डेटा संरक्षण नियमावली, 2025 | p. 1, Rule 1(1) |
| Data Fiduciary | डेटा फ़िड्युसरी | p. 2, Rule 2(1)(ग); rule 3 |
| Significant Data Fiduciary | महत्वपूर्ण डेटा फ़िड्युसरी | p. 8, Rule 13 (heading and sub-rules) |
| Data Principal | डेटा प्रिंसिपल | p. 2, Rule 2(1)(ग); rule 3 |
| Data Processor | डेटा प्रोसेसर | p. 3, Rule 6(1) |
| Consent Manager | सहमति प्रबंधक | p. 2, Rule 4 (heading and sub-rule (1)) |
| Data Protection Board of India | not in the Hindi Rules | The full name does not appear in the Rules, in either the Hindi or the English text. Only the short form 'बोर्ड' (Board) |
| Board | बोर्ड | p. 2, Rule 3(ग)(iii); rule 4 |
| personal data | वैयक्तिक डेटा | p. 2, Rule 3(ख) |
| personal data breach | वैयक्तिक डेटा भंग | p. 3, Rule 6(1); rule 7(2) |
| consent | सहमति | p. 2, Rule 3(ख), 3(ग)(i) |
| verifiable consent | सत्यापन योग्य सहमति | p. 2, Rule 2(1)(घ) |
| withdraw consent | सहमति वापस ले | p. 2, Rule 3(ग)(i) |
| notice | नोटिस | p. 2, Rule 3 (heading and opening words) |
| legitimate use | not in the Hindi Rules | The term does not appear in the Rules (English or Hindi). The Rules refer only to 'uses specified in clause (b) of secti |
| specified purpose | विनिर्दिष्ट उद्देश्य | p. 2, Rule 3(ख)(ii) |
| processing | प्रसंस्करण | p. 2, Rule 3(ख) |
| goods or services | वस्तुओं या सेवाओं | p. 2, Rule 3(ख)(ii) |
| itemised description | मदवार विस्तृत विवरण | p. 2, Rule 3(ख)(i) |
| grievance | शिकायतों | p. 9, Rule 14(3) |
| grievance redressal | शिकायत निवारण | p. 9, Rule 14(3) |
| nominate | नामित | p. 9, Rule 14(4) |
| death or incapacity | not in the Hindi Rules | The phrase does not appear in the Rules (English or Hindi). |
| erasure / erase | मिटा देगा | p. 5, Rule 8(1) |
| correction | not in the Hindi Rules | The term does not appear in the Rules. Rule 13(3) has 'modification', rendered as 'संशोधन'. |
| completion | पूरी होने | p. 5, Rule 8(2) |
| updating | अद्यतन | p. 8, Rule 13(3) |
| summary of personal data | not in the Hindi Rules | The phrase does not appear in the Rules (English or Hindi). |
| child | बच्चा | p. 6, Rule 10 illustration ('C एक बच्चा है') |
| parent | माता-पिता | p. 5, Rule 10(1) |
| lawful guardian | वैध अभिभावक | p. 7, Rule 11 (heading and sub-rule (1)) |
| person with disability | दिव्यांगजन | p. 7, Rule 11 heading and 11(1) ('दिव्यांगजनों') |
| tracking | ट्रैकिंग | p. 18, Fourth Schedule, Part क, items 3-4 |
| behavioural monitoring | व्यवहारिक निगरानी | p. 18, Fourth Schedule, Part क, items 3-4 |
| targeted advertising | not in the Hindi Rules | The term does not appear in the Rules. Only 'advertisement' ('विज्ञापन') appears, in the Fourth Schedule Part ख item 5 ( |
| reasonable security safeguards | उचित सुरक्षा उपाय | p. 3, Rule 6 (heading and sub-rule (1)) |
| logs | लॉग | p. 4, Rule 6(1)(ग), (ङ) |
| retain / retention | बनाए रखना | p. 4, Rule 6(1)(ङ) |
| Data Protection Officer | डेटा संरक्षण अधिकारी | p. 5, Rule 9 |
| contact information | संपर्क जानकारी | p. 5, Rule 9 (heading) |
| identifier | पहचानकर्ता | p. 9, Rule 14(1)(ख), 14(5) |
| complaint | शिकायत | p. 2, Rule 3(ग)(iii) |
| Eighth Schedule to the Constitution | not in the Hindi Rules | The Rules do not refer to the Eighth Schedule to the Constitution (English or Hindi). |
| Third Schedule | तृतीय अनुसूची | p. 5, Rule 8(1) |
| Fourth Schedule | चौथी अनुसूची | p. 8, Rule 12(1), (2) |
| Government | सरकार | p. 5, Rule 8(3) ('सरकार द्वारा अधिसूचित') |
| section | धारा | p. 1, Preamble ('धारा 40 की उप-धारा (1)') |
| rule | नियम | p. 1, Rule 1(2) |
| Act | अधिनियम | p. 2, Rule 2(1)(क) |
| rights of the Data Principal | डेटा प्रिंसिपल के अधिकार | p. 9, Rule 14 (heading) |

## What to check first

The points below came out of the drafting. Each names the key (in `lang/{code}/`) and what a fluent reader should look at. They are a start, not a full review.

### Hindi (`hi`)

In 1.3 the Hindi text was revised against the gazette: 47 strings changed, mostly to use the gazette's terms ("नोटिस" for notice, "वैयक्तिक डेटा" for personal data, "डेटा फ़िड्युसरी" and "डेटा प्रिंसिपल") and to drop the English words that were given in brackets. Check:

- notice.board_*, public.rights.board_*, mail.request_closed.grievance: the Rules use only 'बोर्ड', not the Board's full name. I used 'भारतीय डेटा संरक्षण बोर्ड', the form I believe appears in the Hindi text of the Act. Please confirm against the Act's official Hindi text.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a: 'legitimate use' is not in the Hindi Rules. I used 'वैध उपयोग'. Please check this against the Act's Hindi heading for section 7.
- notice.basis_legitimate_use_a: kept '7(a)' with a Latin letter so it matches the :clause strings, where the code inserts Latin letters. The official Hindi text writes clauses as (क), (ख). If the code can insert Hindi letters, consider using '7(क)' here and in :clause.
- notice.*, public.notice.*, public.signup.no_notice, public.preferences.notice_missing: 'नोटिस' is treated as masculine ('यह नोटिस बताता है', 'प्रकाशित नहीं हुआ है'). Please confirm this is the preferred agreement.
- notice.rights_body, public.rights.right_correction, public.types.updating: used the glossary term 'अद्यतन' instead of the more colloquial 'अपडेट'. Check that this suits ordinary readers on a phone.
- mail.breach.subject, mail.breach.intro: used 'वैयक्तिक डेटा भंग', the glossary's main term. Rule 7 itself uses 'वैयक्तिक डाटा उल्लंघन', so please check which form is preferred.
- public.rights.right_correction, types.correction: 'correction' is not in the Hindi Rules. I used 'सुधार'.
- notice.heading, notice.intro, mail.notice.subject: the verb agrees with :business as a masculine singular subject ('करता है'). Check that this reads well with typical business names.
- public.rights.identifier_default: rephrased so it fits inside 'कृपया :identifier बताएँ'. Check the full sentence reads naturally.
- notice.rights_body uses 'अक्षमता' for 'incapacity'. public.rights.right_nomination and nominations.intro paraphrase 'cannot act' as being unable to act oneself. 'death or incapacity' is not in the Hindi Rules.

### Assamese (`as`)

- All keys: 'personal data' is rendered 'ব্যক্তিগত ডেটা'. The loanword 'ডেটা' follows the Hindi Rules' approach and avoids a clash with 'তথ্য' (information, as in contact information). Check whether 'ব্যক্তিগত তথ্য' is preferred instead.
- Act name (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro): rendered 'ডিজিটেল ব্যক্তিগত ডেটা সুৰক্ষা আইন, 2023'. 'আইন' is used for Act; Assam government texts sometimes use 'অধিনিয়ম'. Please confirm.
- Board name (notice.board_*, public.rights.board_*, mail.request_closed.grievance): 'ভাৰতৰ ডেটা সুৰক্ষা ব'ৰ্ড'. There is no official Assamese name, so this is a descriptive rendering.
- notice.basis_legitimate_use_a: the clause is kept as '7(a)', as in the English. Hindi official text would use '7(क)'. Check whether an Assamese letter is expected.
- Grievance vs complaint (notice.rights_body, notice.board_*, public.requests.intro, types.grievance, mail.request_closed.grievance): both are rendered 'অভিযোগ', mirroring the Hindi Rules' single word 'शिकायत'.
- Legitimate use (notice.basis_legitimate_use): rendered 'বৈধ ব্যৱহাৰ'. The term has no settled Assamese or official Hindi equivalent.
- Personal data breach (mail.breach.subject, mail.breach.intro): rendered 'ব্যক্তিগত ডেটা উলংঘন'. 'ভংগ' is an alternative closer to the Hindi 'भंग'.
- Case suffixes attached to placeholders (notice.heading, notice.intro, public.rights.intro, mail.notice.subject, mail.breach.subject, mail.erasure_warning.intro, public.preferences.given/withdrawn): written as ':business-এ', ':business-ত', '":purpose"ৰ'. These read correctly for most names, but check them with real business names.

### Bengali (`bn`)

- Terminology (all keys): 'personal data' is 'ব্যক্তিগত ডেটা'. 'ডেটা' is kept in line with the Hindi 'डेटा' rather than 'তথ্য'. The Act is 'ডিজিটাল ব্যক্তিগত ডেটা সুরক্ষা আইন, 2023'. Please check that this matches any official Bengali rendering of the Act.
- notice.board_heading, notice.board_with_url, notice.board_without_url, rights.board_*, mail.request_closed.grievance: 'Data Protection Board of India' is rendered 'ভারতের ডেটা সুরক্ষা বোর্ড'. The official Hindi Rules give no full name for the Board, so check this rendering.
- Grievance and complaint: both are rendered with 'অভিযোগ', following the Hindi Rules, which use 'शिकायत' for both. To keep them apart, 'raise a grievance' is 'অভিযোগ জানানো' and 'complain to the Board' is 'অভিযোগ দায়ের করা'. Check notice.rights_body, notice.board_*, rights.right_grievance, types.grievance and mail.request_closed.grievance.
- notice.basis_legitimate_use, preferences.legitimate_use: 'legitimate use' is rendered 'বৈধ ব্যবহার'. The term is not in the Hindi Rules.
- notice.basis_legitimate_use_a: the clause is kept as '7(a)' in Latin letters, to match the :clause value the code inserts. Bengali legal texts may write it as '7(ক)'.
- 'Updating' is rendered as the loanword 'আপডেট', which is common in Indian Bengali, rather than 'হালনাগাদ'. Check notice.rights_body, rights.right_correction and types.updating.
- Parent: rendered 'বাবা-মা' throughout, and 'lawful guardian' as 'বৈধ অভিভাবক' (preferences.guardian_*). 'বাবা-মা' can read as both parents, while the English says 'a parent'. Check whether 'বাবা/মা' or 'পিতামাতার একজন' is preferred.
- Child: rendered 'শিশু' (notice.tracking, preferences.not_for_children). Under the Act a child is anyone under 18, so check that teenage readers understand 'শিশু' this way.

### Bodo (`brx`)

- General: Bodo has no settled DPDP legal vocabulary. I used Bodo words where I believe they are common: गनायथि (consent), मोनथाय (rights), थांखि (purpose), बिनती (request), फिननाय (reply), आयेन (Act), दफा (section), रैखाथि (protection). A fluent reviewer should confirm all of these. Affects most keys.
- personal data: I kept the official Hindi 'वैयक्तिक डेटा' throughout, following the Hindi approach (notice.heading, rights_body and others). A native Bodo term such as 'गावारि डेटा' may read more naturally.
- Act name: 'डिजिटल वैयक्तिक डेटा रैखाथि आयेन, 2023' is my rendering. Check against any official Bodo translation (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro).
- Data Protection Board of India: rendered 'भारतनि डेटा रैखाथि बोर्ड' with the loanword 'बोर्ड', as in Hindi (notice.board_*, public.rights.board_*, mail.request_closed.grievance).
- grievance and complaint: both rendered 'नालिस', mirroring the Hindi Rules, which use 'शिकायत' for both. Verify this is clear (notice.rights_body, notice.board_*, public.requests.intro, public.types.grievance).
- erase/erasure: 'खोमोर' / 'खोमोरनाय' is uncertain. Please confirm or replace with the established Bodo word for delete/erase (notice.rights_body, public.rights.right_erasure, public.types.erasure, mail.erasure.*, mail.erasure_warning.*).
- correct/complete/update: rendered 'सुद्राय', 'पूरा खालाम' and 'गोदान खालाम'. 'सुद्राय' especially needs checking (public.rights.right_correction, public.types.*).
- contact: 'फोनांजाब' (contact/connection) needs confirmation (notice.contact_heading, public.preferences.age_change, mail.contact_heading, mail.breach.contact).

### Dogri (`doi`)

- General: verb morphology (1st-person plural present forms like 'बरतने आं', 'दिन्ने आं', 'करने आं'; progressive 'करा दे आं' / 'करा करदे आं'; 3rd-person future 'जाग') follows standard Jammu Dogri as best understood. A native Dogri speaker should check these forms throughout and make them consistent.
- General: The Dogri danda '।' is used as the sentence end mark. Dogri-style spellings are used: 'भाशा', 'पुश्टी', 'संतुश्ट', 'प्रकाशत', 'शामल'. Check these against the house orthography (for example the Dogri Sanstha or Sahitya Akademi style).
- General: 'वैयक्तिक डेटा', 'सहमति', 'डेटा भंग', 'प्रसंस्करण', 'लॉग', 'नामित', 'अद्यतन' and 'व्यवहारिक निगरानी' are taken from the official Hindi Rules glossary, because there is no settled Dogri legal vocabulary. They are formal; check that ordinary readers understand them.
- notice.board_heading, public.rights.board_*, mail.request_closed.grievance: 'Data Protection Board of India' does not appear in the Hindi Rules. 'भारतीय डेटा संरक्षण बोर्ड' follows the Hindi text of the Act; please confirm.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.preferences.legitimate_use: 'legitimate use' is rendered as 'वैध उपयोग', following the Hindi Act's section 7 wording. Clause letters are kept as Latin '7(a)', matching the Latin value the code inserts for :clause.
- notice.title, public.nav.notice: 'Privacy notice' is rendered as 'निजता नोटिस'. Check whether 'प्राइवेसी नोटिस' is more familiar to readers.
- notice.rights_body, public.rights.right_nomination, public.nominations.intro: 'incapacity' / 'cannot act' is rendered with 'असमर्थता' / 'आपूं कम्म करने च असमर्थ'. These terms are not in the Hindi Rules.
- public.rights.right_erasure: 'unless' is rendered as 'सिवाए इसदे जे ... होऐ'. Check that both exceptions keep their exact meaning.

### Gujarati (`gu`)

- Register: the strings use 'તમે' with plural verb forms, the standard polite Gujarati 'you'. They do not use the more formal 'આપ'. Please check that this suits the audience.
- Law names (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro): I am not aware of an official Gujarati translation of the Act. 'ડિજિટલ વ્યક્તિગત ડેટા સંરક્ષણ અધિનિયમ, 2023' uses 'વ્યક્તિગત', the common Gujarati word for 'personal', instead of the Hindi-style 'વૈયક્તિક'. 'વ્યક્તિગત ડેટા' is used the same way throughout.
- Board name (notice.board_heading, notice.board_with_url, notice.board_without_url, public.rights.board_*, mail.request_closed.grievance): 'ભારતીય ડેટા સંરક્ષણ બોર્ડ' follows the Hindi Act's 'भारतीय डेटा संरक्षण बोर्ड'. Please confirm.
- Grievance vs complaint (notice.board_with_url, notice.board_without_url, public.rights.board_*, mail.request_closed.grievance, types.grievance): both are rendered 'ફરિયાદ', as the official Hindi uses 'शिकायत' for both. Please check that the board sentences still read clearly.
- Legitimate use (notice.basis_legitimate_use): 'કાયદેસર ઉપયોગ' has no settled Gujarati legal equivalent. The same adjective is used for 'lawful guardian' ('કાયદેસર વાલી').
- Clause letters (notice.basis_legitimate_use, notice.basis_legitimate_use_a): '7(a)' and '7(:clause)' keep Latin letters to match the code-inserted value. Gujarati legal texts might use '7(ક)'.
- Erasure (types.erasure, rights.right_erasure, erasure.*, erasure_warning.*): 'ભૂંસી નાખવું' mirrors the Hindi 'मिटा देना'. For 'deleted' in erasure.logs I used 'કાઢી નાખવામાં આવે છે' to keep the two English verbs distinct.
- Updating (types.updating, rights.right_correction, notice.rights_body): 'અદ્યતન કરવું' follows the Hindi 'अद्यतन'. Ordinary readers may find 'અપડેટ કરવું' easier. Please decide.

### Kannada (`kn`)

- All keys: 'personal data' is rendered as 'ವೈಯಕ್ತಿಕ ಡೇಟಾ', following the official Hindi transliteration approach. 'ವೈಯಕ್ತಿಕ ದತ್ತಾಂಶ' is the more formal Kannada alternative. Please confirm the preferred term.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro: the Act's name is rendered as 'ಡಿಜಿಟಲ್ ವೈಯಕ್ತಿಕ ಡೇಟಾ ಸಂರಕ್ಷಣಾ ಅಧಿನಿಯಮ, 2023'. 'ಅಧಿನಿಯಮ' is used for Act, as in Karnataka statutes. Please check whether an official Kannada title exists.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.preferences.legitimate_use: 'section' is rendered as 'ಪ್ರಕರಣ', the usual term in Kannada statutes. The clause letter is kept Latin, as in 7(a), to match the :clause value the code inserts.
- notice.basis_legitimate_use: 'legitimate use' has no term in the Rules. It is rendered as 'ನ್ಯಾಯಸಮ್ಮತ ಬಳಕೆ'. Please check.
- All grievance and complaint keys: 'grievance' is rendered as 'ಕುಂದುಕೊರತೆ' (the usual administrative term) and 'complaint' as 'ದೂರು', to keep them distinct as in the English.
- notice.board_heading and related keys: 'Data Protection Board of India' is rendered as 'ಭಾರತೀಯ ಡೇಟಾ ಸಂರಕ್ಷಣಾ ಮಂಡಳಿ', with 'ಮಂಡಳಿ' for Board. Please check against any official Kannada usage.
- public.preferences.guardian_* and public.guardian_verifier_missing: 'parent' is rendered as 'ಪೋಷಕರು', 'guardian' as 'ಪಾಲಕರು' and 'lawful guardian' as 'ಕಾನೂನುಬದ್ಧ ಪಾಲಕರು'. In everyday Kannada these words overlap, so please check that the distinction is clear.
- notice.title and related keys: 'notice' is rendered as 'ಸೂಚನೆ' ('privacy notice' = 'ಗೌಪ್ಯತಾ ಸೂಚನೆ'). 'ನೋಟಿಸ್' is the alternative if a transliteration is preferred.

### Kashmiri (`ks`)

- General: Kashmiri verb inflection (ergative past, perfect participles, agent suffixes) and the spelling of the special Kashmiri vowel marks (ٲ، ٕ، ۍ، ۄ، ٚ) should be checked by a fluent reader throughout. The text uses the respectful forms تۄہؠ / تُہُنٛد and respectful imperatives in -ِو.
- Terms: 'consent' is rendered as رضامٔندی, 'personal data' as ذٲتی ڈیٹا, 'grievance' and 'complaint' both as شکایت (the official Hindi text also uses one base word for both), 'nominate' as نامزد کرُن, 'erase' as مِٹاوُن, 'notice' as نوٹس, 'logs' as لاگ, 'processing' as پروسیسنگ and 'tracking' as ٹریکنگ. Please check that each term is used the same way everywhere.
- notice.title / nav.notice: 'Privacy notice' is rendered as رازداری نوٹس. Check whether readers would find پرائیویسی نوٹس more natural.
- notice.basis_legitimate_use / preferences.legitimate_use: 'legitimate use' has no official Hindi term. It is rendered as جٲیز استعمال. Section is دفعہ. The Act name is given as ڈیجیٹل ذٲتی ڈیٹا تحفظ ایکٹ، 2023.
- notice.basis_legitimate_use_a: the clause letter is kept as 7(a), to match the Latin letters that the code inserts through :clause.
- board_heading / board_with_url / board_without_url / mail.request_closed.grievance: the full name 'Data Protection Board of India' does not appear in the Hindi Rules. It is transliterated as ڈیٹا پروٹیکشن بورڈ آف انڈیا. A translated name such as بھارتُک ڈیٹا تحفظ بورڈ is the alternative.
- notice.rights_body: 'incapacity' is rendered as نااہلیت. In rights.right_nomination and nominations.intro, 'cannot act' is rendered in plain words: پانہٕ کینٛہہ کٔرِتھ ہیٚکِو نہٕ. Check that the legal meaning is kept.
- preferences.guardian_*: 'parent' is rendered as والدین, which reads as 'parents', and 'lawful guardian' as قانوٗنی سرپرست. Check whether a singular form such as مٲلۍ یا ماجہِ is better.

### Konkani (`kok`)

- Global: 'withdraw consent' is rendered 'सहमती परत घेवप'. The more idiomatic Goan 'फाटीं घेवप' is an alternative. Check which is preferred and keep it consistent across keys.
- Global: 'grievance' is rendered 'गाऱ्हाणें' and 'complaint' (to the Board) 'तक्रार', so the two stay distinct. The Hindi Rules use 'शिकायत' for both. Check this choice in the board_* keys, requests.intro, types.grievance and request_closed.grievance.
- Global: 'section' is rendered 'कलम', the usual Konkani/Marathi legal term, instead of the Hindi 'धारा'. Check basis_legitimate_use, basis_legitimate_use_a and preferences.legitimate_use.
- notice.basis_legitimate_use_a: the clause is kept as '7(a)', matching the Latin letter the code inserts for :clause. The official Hindi text uses '7(क)'.
- Global: 'legitimate use' (no term in the Rules) is rendered 'वैध वापर'. Check basis_legitimate_use and its related keys.
- Global: 'Data Protection Board of India' is rendered 'भारतीय डेटा संरक्षण बोर्ड', following the Hindi Act's name and the Rules' use of 'बोर्ड'.
- Global: 'processing' is rendered 'प्रक्रिया', the common Konkani word, instead of the Hindi 'प्रसंस्करण'. Check erasure.logs.
- Global: 'nominate' is rendered 'नामनिर्देशीत करप' and 'nomination' 'नामनिर्देशन'. Check the nominations.* keys and the rights_body and right_nomination keys.

### Maithili (`mai`)

- Register: the polite 'अहाँ' with honorific verb forms (सकैत छी, देलहुँ, करू, दिअ) is used throughout. The business speaks as 'हमसभ' (oblique 'हमरासभ'). Please check whether the higher honorific 'अपने' is preferred.
- notice.board_heading, notice.board_with_url, notice.board_without_url, public.rights.board_*, mail.request_closed.grievance: the full name 'Data Protection Board of India' is not in the Hindi Rules. I used 'भारतीय डेटा संरक्षण बोर्ड', following the Hindi text of the Act. Please verify.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a, preferences.legitimate_use: 'legitimate use' is not in the Rules. I used 'वैध उपयोग', the term in the Hindi Act's section 7 heading.
- notice.basis_legitimate_use_a: I kept '7(a)' in Latin script, to match the :clause values the code inserts. The official Hindi would write '7(क)'. Please decide which to use.
- Erasure (notice.rights_body, rights.right_erasure, types.erasure, mail.erasure.*, erasure_warning.*): I used the native Maithili verb 'मेटाएब / मेटा देब', which matches the Hindi 'मिटा देगा'.
- Grievance and complaint are both 'शिकायत', following the Hindi Rules. In the board_* strings 'शिकायत' therefore appears twice. Please check that this reads clearly.
- rights.identifier_body: I restructured the sentence as 'ई जानकारी दिअ: :identifier' so that any inserted value, including the long rights.identifier_default phrase, fits the grammar.
- preferences.guardian_active, mail.breach.aware, erasure_warning.*: I used ':date केँ' for 'on :date'. Please check that this sounds natural.

### Malayalam (`ml`)

- Register: the polite standard 'നിങ്ങൾ' is used throughout, with neutral polite imperatives ending in '-ുക'. 'താങ്കൾ' would be more formal; check which suits the audience.
- Act name: rendered 'ഡിജിറ്റൽ വ്യക്തിഗത ഡാറ്റ സംരക്ഷണ നിയമം, 2023', using 'നിയമം' for Act and 'വ്യക്തിഗത ഡാറ്റ' for personal data. Check against any official Malayalam rendering (e.g. Kerala Government), which may use 'വിവരം' instead of 'ഡാറ്റ' (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro).
- Data Protection Board of India: transliterated as 'ഡാറ്റ പ്രൊട്ടക്ഷൻ ബോർഡ് ഓഫ് ഇന്ത്യ', following the Hindi approach of transliterating names. A Malayalam calque ('ഇന്ത്യൻ ഡാറ്റ സംരക്ഷണ ബോർഡ്') is an alternative (notice.board_*, public.rights.board_*, mail.request_closed.grievance).
- Grievance and complaint: both are rendered 'പരാതി', matching the Hindi text, which uses 'शिकायत' for both. Check whether a distinction is wanted (notice.rights_body, notice.board_*, public.types.grievance, mail.request_closed.grievance).
- Legitimate use: rendered 'നിയമാനുസൃത ഉപയോഗം'. The term has no official equivalent (notice.basis_legitimate_use).
- Clause letters: '7(a)' keeps the Latin letter, because :clause is inserted by the code in Latin letters. Malayalam legal texts may use other clause markers (notice.basis_legitimate_use_a).
- Incapacity: rendered 'അപ്രാപ്തി' in notice.rights_body, and as 'പ്രവർത്തിക്കാൻ കഴിയാതാവുക' (unable to act) in public.rights.right_nomination and public.nominations.intro, following the plainer English there. Please check.
- Parent or guardian: rendered 'മാതാപിതാക്കളിൽ ഒരാളോ രക്ഷാകർത്താവോ' (one of the parents or a guardian). Lawful guardian is 'നിയമപരമായ രക്ഷാകർത്താവ്' (public.preferences.guardian_*).

### Manipuri (`mni`)

- Read the whole text; the drafting left no specific notes.

### Marathi (`mr`)

- Register (all keys): the reader is addressed as 'तुम्ही' (respectful plural), not 'आपण'. 'आपण' can be confused with inclusive 'we'. Please check that this is the preferred register.
- Term 'notice' (notice.*, nav.notice, signup.*, mail.notice.*): rendered as the established Marathi 'सूचना' ('गोपनीयता सूचना'), not the Hindi Rules' transliteration 'नोटिस'.
- Term 'consent' (all keys): rendered as Marathi 'संमती' (Hindi 'सहमति'). 'Withdraw consent' is rendered as 'संमती मागे घेणे'.
- Term 'Data Protection Board of India' (board_*, rights.board_*, mail.request_closed.grievance): rendered as 'भारतीय डेटा संरक्षण मंडळ', using 'मंडळ' for Board. Please check against any official Marathi usage.
- Grievance vs complaint (rights_body, board_*, requests.intro, types.grievance, mail.request_closed.grievance): both are rendered as 'तक्रार', as the Hindi Rules use 'शिकायत' for both. The sentences distinguish 'raise with us' ('नोंदवा') from 'complain to the Board' ('मंडळाकडे तक्रार करा').
- Term 'section' is rendered as the Marathi legal 'कलम'. In notice.basis_legitimate_use_a the clause is kept as '7(a)' in Latin letters, because the :clause placeholder inserts Latin letters. Marathi legal texts may write '7(क)'.
- Term 'legitimate use' (notice.basis_legitimate_use): rendered as 'वैध वापर'. There is no Rules term for it.
- Term 'erase/erasure' (rights.right_erasure, types.erasure, mail.erasure.*, mail.erasure_warning.*): rendered as 'पुसून टाकणे' for plain readability.

### Nepali (`ne`)

- Global: I used everyday Nepali terms rather than copying the Hindi Gazette terms. These are 'व्यक्तिगत डाटा' (personal data, not Hindi 'वैयक्तिक डेटा'), 'ऐन' (Act), 'दफा' (section), 'सूचना' (notice), 'प्रशोधन' (processing) and 'अद्यावधिक' (update). Please confirm this suits Nepali-speaking readers in India (Sikkim, Darjeeling). Also check 'डाटा' vs 'डेटा'.
- Global: grievance is 'गुनासो' and complaint to the Board is 'उजुरी'. Hindi uses one word for both. Nepali administrative usage separates them, and keeping them apart preserves the two-step meaning in request_closed.grievance and the board_* strings.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a: the clause letters stay in Latin script ('7(a)', '7(:clause)') to match what the code inserts. Nepali legal style would normally use 'क', 'ख'.
- Global: the Act's name is given as 'डिजिटल व्यक्तिगत डाटा संरक्षण ऐन, 2023' and the Board as 'भारतको डाटा संरक्षण बोर्ड'. Neither has an official Nepali rendering. Please check.
- Global (notice.basis_legitimate_use and the preferences legitimate-use strings): 'legitimate use' is rendered as 'वैध प्रयोग'. There is no settled term, and the term is not in the Hindi Rules.
- mail.breach.subject, mail.breach.intro: 'personal data breach' is rendered as 'व्यक्तिगत डाटा उल्लङ्घन'. 'चुहावट' (leak) would be narrower, so I did not use it.
- rights.right_erasure: the 'unless' condition is split into a second sentence ('...भएमा यो लागू हुँदैन'). Please check that the exceptions read exactly as in the English.
- rights.intro: ':business' appears twice so the Nepali sentence reads naturally. Please confirm the code allows a repeated placeholder. If not, replace the first ':business ले' with 'उसले'.

### Odia (`or`)

- Global: 'data' is rendered as 'ତଥ୍ୟ' and 'personal data' as 'ବ୍ୟକ୍ତିଗତ ତଥ୍ୟ' throughout, including the law name and the Board name. The official Hindi text instead transliterates 'डेटा'. If 'ଡାଟା' is preferred, replace it consistently in every string.
- Law name (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro): 'ଡିଜିଟାଲ ବ୍ୟକ୍ତିଗତ ତଥ୍ୟ ସୁରକ୍ଷା ଅଧିନିୟମ, 2023'. No official Odia title has been verified. Please check against any Odisha Government or Official Language translation.
- Board name (notice.board_*, public.rights.board_*, mail.request_closed.grievance): 'ଭାରତୀୟ ତଥ୍ୟ ସୁରକ୍ଷା ବୋର୍ଡ'. This is not an attested official Odia name.
- Grievance and complaint are both rendered 'ଅଭିଯୋଗ', mirroring the Hindi Rules, which use 'शिकायत' for both (notice.rights_body, notice.board_*, mail.request_closed.grievance). Please check that the two steps are still clear to readers.
- Clause letters: notice.basis_legitimate_use_a keeps '7(a)' in Latin script to match the :clause placeholder in notice.basis_legitimate_use. Odia legal style would use '7(କ)'.
- 'Parent' is rendered 'ପିତାମାତା' (parents), mirroring the Hindi 'माता-पिता' (preferences.guardian_*). 'Lawful guardian' is 'ବୈଧ ଅଭିଭାବକ'.
- 'Erase' is 'ବିଲୋପ କରିବା'. 'Update' is 'ଅଦ୍ୟତନ', which some readers may know better as 'ଅପଡେଟ୍' (types.updating, rights.right_correction, notice.rights_body).
- 'Personal data breach' is 'ବ୍ୟକ୍ତିଗତ ତଥ୍ୟ ଉଲ୍ଲଂଘନ' (breach.subject, breach.intro). The Hindi Rules use both 'भंग' and 'उल्लंघन'.

### Punjabi (`pa`)

- Read the whole text; the drafting left no specific notes.

### Sanskrit (`sa`)

- All keys: Sanskrit has no settled DPDP vocabulary. 'personal data' is rendered as 'वैयक्तिकदत्तांशः' (दत्तांश as used on Sanskrit Wikipedia) instead of the official Hindi transliteration 'डेटा', because 'डेटा' cannot be declined naturally. Please check this choice. If 'डेटा' is preferred, it must be changed everywhere, including the law name.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro: The Act's name is given as ‘डिजिटल-वैयक्तिक-दत्तांश-संरक्षण-अधिनियमः, 2023’ followed by इत्यस्य. Readers who know only the official Hindi name (डिजिटल वैयक्तिक डेटा संरक्षण अधिनियम, 2023) may find it less recognisable.
- notice.board_*, public.rights.board_*, mail.request_closed.grievance: 'Data Protection Board of India' is rendered as 'भारतीय-दत्तांश-संरक्षण-मण्डलम्'. The full name does not appear in the Hindi Rules, and 'मण्डलम्' stands for Hindi 'बोर्ड'.
- Grievance vs complaint: grievance (to the business) is 'परिवेदना' and complaint (to the Board) is 'परिवादः'. The official Hindi uses 'शिकायत' for both. Please check that the distinction reads clearly.
- notice.title, nav.notice, etc.: 'notice' is rendered as 'विज्ञप्तिः', and 'privacy' as 'निजता'. 'सूचना' was avoided, following the Hindi glossary note.
- Register and gender: the reader is addressed as 'भवान्' with third-person verbs. Passive constructions are used where possible, but some forms remain masculine by default (e.g. 'सन्तुष्टः' in board_* and mail.request_closed.grievance). Consider whether a gender-neutral solution is needed.
- notice.withdraw_body, public.preferences.legitimate_use_a: These quote the button labels 'सहमतिं प्रत्याहरतु' and 'इमम् उपयोगं स्थगयतु'. They must stay identical to public.preferences.withdraw and public.preferences.object. No word for 'button' is used; the text says 'press "..."' instead.
- notice.basis_legitimate_use_a: '7(a)' keeps the Latin clause letter so that it matches the :clause placeholder values. The official Hindi uses (क).

### Santali (`sat`)

- Read the whole text; the drafting left no specific notes.

### Sindhi (`sd`)

- Law name: 'Digital Personal Data Protection Act, 2023' is rendered as 'ڊجيٽل ذاتي ڊيٽا تحفظ ايڪٽ، 2023' (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.rights.intro). Please check that this is a recognisable Sindhi form. A transliteration ('ڊجيٽل پرسنل ڊيٽا پروٽيڪشن ايڪٽ') is an alternative.
- Data Protection Board of India is rendered as 'ڀارت جو ڊيٽا تحفظ بورڊ' (oblique 'ڀارت جي ڊيٽا تحفظ بورڊ'). There is no official Sindhi name, so please check (notice.board_*, public.rights.board_*, mail.request_closed.grievance).
- 'personal data' = 'ذاتي ڊيٽا'. 'ڊيٽا' is treated as masculine throughout (اوهان جو ڊيٽا، ڪهڙو، ميٽيو ويندو). Please check that this gender agreement reads naturally to Indian Sindhi readers.
- 'section' is rendered as legal 'دفعو' (oblique 'دفعي'). The clause letter is kept as Latin '7(a)' and '7(:clause)' to match the letter the code inserts (notice.basis_legitimate_use, notice.basis_legitimate_use_a, public.preferences.legitimate_use).
- 'legitimate use' = 'جائز استعمال'. This term is not in the Hindi Rules (notice.basis_legitimate_use).
- 'erase/erasure' = 'ميٽڻ'. 'updating' = 'تازو ڪرڻ'. 'correction' = 'درستي / درست ڪرڻ'. These are plain-language choices; check them against types.* and rights.right_* for consistency.
- 'incapacity' is rendered as 'معذوري' in notice.rights_body. In public.rights.right_nomination and nominations.intro it is rendered as 'پاڻ عمل نه ڪري سگهڻ' (following the English 'cannot act'). 'معذوري' may also be read as 'disability', so please check.
- 'personal data breach' = 'ذاتي ڊيٽا جي ڀڃڪڙي' (mail.breach.*). Please check the register.

### Tamil (`ta`)

- All keys naming the Act (e.g. notice.basis_legitimate_use, public.rights.intro): 'டிஜிட்டல் தனிநபர் தரவுப் பாதுகாப்புச் சட்டம், 2023' follows common government/PIB Tamil usage. I found no official Tamil text of the Act. Please confirm.
- 'personal data' is rendered 'தனிநபர் தரவு' throughout, to match the Act's name. 'தனிப்பட்ட தரவு' is also common. Check that the term reads naturally to readers.
- Board keys (notice.board_*, public.rights.board_*, mail.request_closed.grievance): 'இந்திய தரவுப் பாதுகாப்பு வாரியம்' is a descriptive rendering. The official Hindi Rules use only 'बोर्ड', so there is no settled Tamil name.
- notice.basis_legitimate_use: 'legitimate use' is rendered 'சட்டப்படியான பயன்பாடு'. Neither the Rules nor any established Tamil source gives a settled term.
- notice.basis_legitimate_use, notice.basis_legitimate_use_a: the clause letter is kept in Latin (7(a), 7(:clause)) to match the letter the code inserts. Tamil legal texts may use (அ).
- grievance vs complaint: 'குறை' is used for a grievance raised with the business and 'புகார்' for a complaint to the Board, to keep the two steps distinct (notice.board_*, mail.request_closed.grievance, types.grievance).
- child: 'குழந்தை' is used, as in the Act's sense of under 18 (notice.tracking, preferences.not_for_children). Some readers may expect 'சிறார்' for teenagers.
- public.rights.identifier_body: restructured as '...இதை வழங்கவும்: :identifier.' because the default identifier ends in a noun that takes an awkward case suffix.

### Telugu (`te`)

- Act name (all keys citing it): no official Telugu text of the DPDP Act was available. I used 'డిజిటల్ వ్యక్తిగత డేటా పరిరక్షణ చట్టం, 2023'. Please check it against any official Telugu rendering.
- notice.board_heading and related board_* keys, mail request_closed.grievance: 'Data Protection Board of India' is rendered as 'భారత డేటా పరిరక్షణ బోర్డు'. Neither the Hindi nor the English Rules give the full name.
- Grievance vs complaint (rights_body, board_*, types.grievance, requests.intro, mail request_closed.grievance): following the Hindi text, which uses शिकायत for both, I use 'ఫిర్యాదు' for both a grievance to the business and a complaint to the Board. Please check that the two steps stay clear to readers.
- basis_legitimate_use: 'legitimate use' is rendered as 'చట్టబద్ధమైన వినియోగం'. There is no settled Telugu legal term. 'న్యాయసమ్మతమైన వినియోగం' is a possible alternative.
- Section references (basis_legitimate_use, basis_legitimate_use_a, preferences.legitimate_use): I used the transliteration 'సెక్షన్' and kept the Latin clause letter '7(a)'. Please confirm this is preferred over 'విభాగం' or a Telugu letter.
- Purpose: rendered as 'ఉద్దేశం' throughout, matching Hindi 'उद्देश्य', to avoid confusion with 'ప్రయోజనం' (benefit), which would clash with notice.label_uses.
- Nominate (rights_body, rights.right_nomination, nominations.*): I used the familiar loanword 'నామినేట్ చేయండి' rather than the formal 'నామనిర్దేశం'.
- rights_body: 'incapacity' is rendered as 'అసమర్థత'. In rights.right_nomination and nominations.intro, 'cannot act' is rendered as 'స్వయంగా వ్యవహరించలేని స్థితి'.

### Urdu (`ur`)

- All keys: the Act's name is translated as 'ڈیجیٹل ذاتی ڈیٹا تحفظ ایکٹ، 2023'. A transliteration ('ڈیجیٹل پرسنل ڈیٹا پروٹیکشن ایکٹ') may be more recognisable to some readers. Please confirm which form to use.
- notice.board_heading, board_with_url, board_without_url, rights.board_*, mail.request_closed.grievance: 'Data Protection Board of India' is rendered as 'بھارتی ڈیٹا تحفظ بورڈ'. This mirrors the Hindi Act's 'भारतीय डेटा संरक्षण बोर्ड'. Please check it against any official Urdu usage.
- Grievance and complaint keys: both 'grievance' (to us) and 'complaint' (to the Board) are rendered as 'شکایت', as in the official Hindi. Context (to us vs. to the Board) is what tells them apart.
- notice.rights_body: 'incapacity' is rendered as 'معذوری'. This could be read as 'disability' in general. In public.rights.right_nomination and nominations.intro, 'cannot act' is rendered as 'خود کچھ نہ کر سکیں'.
- mail.breach.*: 'personal data breach' is rendered as 'ذاتی ڈیٹا کی خلاف ورزی'. This follows the Hindi rule 7 term 'उल्लंघन'. Please check that it reads naturally.
- preferences.tracking, notice.tracking: 'behavioural monitoring' is rendered as 'رویّے کی نگرانی'.
- notice.basis_legitimate_use, preferences.legitimate_use: 'legitimate use' is rendered as 'جائز استعمال'. There is no official term for it.
- basis_legitimate_use_a: the clause letter is kept as Latin '7(a)' to match the :clause placeholder style. Urdu legal texts may use '(الف)'.
