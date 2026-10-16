# Translations

Section 5(3) and section 6(3) of the Digital Personal Data Protection Act, 2023 ask for the notice and every consent request to be offered "in English or any language specified in the Eighth Schedule to the Constitution". This file says which languages the package ships, how to add one, and which Hindi strings still need a fluent reader.

## What ships

- **English** is ready.
- **Hindi** is written and waits for review. Its status is `needs_review`, which means it can be offered, but a fluent speaker should read it before you rely on it. The strings that need a second look are listed under "For review" below.
- **The other 21 Eighth Schedule languages** have a `meta.php` file with the status `needed`. They have no text yet. A language with the status `needed` cannot be offered or published.

The dashboard (the staff screens) is English only. The translations cover what a Data Principal sees: the notice, the rights page, the consent choices, the requests pages and the emails.

## How a language is offered

Three things decide it:

1. **The status** in `lang/{code}/meta.php`: `ready`, `needs_review` or `needed`. Only `needed` blocks a language.
2. **The `locales` list** in `config/data-rights.php`. Only the codes listed there are offered. The default is `['en', 'hi']`.
3. **The text of your purposes.** Every purpose needs a title, a description, a description of the goods or services, the list of data and (when set) the withdrawal effect in each offered language.

`php artisan data-rights:notice` refuses to publish, and names what is missing, when a listed language has the status `needed` or when a purpose has no text in an offered language. `php artisan data-rights:check` reports a listed `needed` language as an error, and warns when Hindi is not offered.

If you keep the default `locales` and write only English text for your own purposes, the notice command stops at the Hindi text. Either write the Hindi text for your purposes, or set `'locales' => ['en']`.

## Add a language

Use the code from the table below.

1. Publish the language files into your app:

   ```
   php artisan vendor:publish --tag=data-rights-lang
   ```

   This copies every language folder to `lang/vendor/data-rights/`.

2. In `lang/vendor/data-rights/{code}/`, add three files: `notice.php`, `public.php` and `mail.php`. Start from copies of the English files in `lang/vendor/data-rights/en/`.
3. Translate the values. Do not change the keys. Keep every `:placeholder` exactly as it is (for example `:business`, `:url`, `:date`, `:days`). Keep the quotation marks around the button names in the sentences that name them.
4. In `lang/vendor/data-rights/{code}/meta.php`, set:
   - `status` to `needs_review` until someone fluent has read it, then `ready`;
   - `name` to the name of the language written in that language;
   - `direction` to `rtl` for a right-to-left script and `ltr` for the rest.
5. Add the code to `locales` in `config/data-rights.php`, and add the language to the text of each of your purposes.
6. Run `php artisan data-rights:notice`. It publishes a new notice version for every offered language. Then run `php artisan data-rights:check`.

People switch language with `?lang={code}` on the notice, rights and preferences pages. The language switcher lists only the offered languages, by their own names.

A key you leave out is shown in your app's fallback language (usually English). Translate every key, because a half-translated screen is hard to read.

Some of these languages are written in more than one script (Kashmiri, Sindhi and Manipuri, for example). The script is your translator's choice. Set `direction` to match it. The shipped stubs assume a right-to-left script for Urdu, Kashmiri and Sindhi.

Please do not machine-translate a legal notice and publish it as it is. Ask a fluent speaker to read it, and keep the status at `needs_review` until they have.

## The 22 languages of the Eighth Schedule

| Code | Language | Direction | Status |
| --- | --- | --- | --- |
| `hi` | Hindi | ltr | needs_review |
| `as` | Assamese | ltr | needed |
| `bn` | Bengali | ltr | needed |
| `brx` | Bodo | ltr | needed |
| `doi` | Dogri | ltr | needed |
| `gu` | Gujarati | ltr | needed |
| `kn` | Kannada | ltr | needed |
| `ks` | Kashmiri | rtl | needed |
| `kok` | Konkani | ltr | needed |
| `mai` | Maithili | ltr | needed |
| `ml` | Malayalam | ltr | needed |
| `mni` | Manipuri | ltr | needed |
| `mr` | Marathi | ltr | needed |
| `ne` | Nepali | ltr | needed |
| `or` | Odia | ltr | needed |
| `pa` | Punjabi | ltr | needed |
| `sa` | Sanskrit | ltr | needed |
| `sat` | Santali | ltr | needed |
| `sd` | Sindhi | rtl | needed |
| `ta` | Tamil | ltr | needed |
| `te` | Telugu | ltr | needed |
| `ur` | Urdu | rtl | needed |

English (`en`) is not on the Eighth Schedule. It is always available as the default language, and its status is `ready`.

In the shipped stubs, `name` is the English name of the language. Native names are not guessed. Put the native name there when you translate.

## For review

The Hindi text is plain, respectful Hindi (Devanagari). It uses "आप" for the reader and "हम" for the business, and ends sentences with "।". Legal terms are given in Hindi, with the English term in brackets the first time it appears on a screen or in an email.

No fluent reader has checked it yet. Please check the points below. Each line names the key (in `lang/hi/`) and what to check.

| # | Where | What the text says | What to check |
| --- | --- | --- | --- |
| 1 | everywhere | "व्यक्तिगत डेटा" for personal data | The Hindi title of the Act in the gazette appears to use "वैयक्तिक". The text uses "व्यक्तिगत" because it is the everyday word. Which one do you want? |
| 2 | `notice.board_*`, `public.rights.board_*`, `mail.request_closed.grievance` | "डेटा संरक्षण बोर्ड (Data Protection Board of India)" | Should the Hindi name be "भारतीय डेटा संरक्षण बोर्ड"? The text uses the shorter form. |
| 3 | `notice.title`, `public.nav.notice`, `public.notice.title` | "गोपनीयता सूचना" for Privacy notice | "सूचना" also means "information". Alternatives: "निजता सूचना", "गोपनीयता नोटिस". |
| 4 | `notice.tracking`, `public.preferences.tracking` | "ट्रैकिंग या व्यवहार पर नज़र रखना" for tracking or behavioural monitoring | The Hindi text of section 9(3) may use other words. Match it if you can. |
| 5 | `mail.breach.subject`, `mail.breach.intro` | "व्यक्तिगत डेटा उल्लंघन" for personal data breach | The standard Hindi term is not known. Alternatives: "डेटा भंग", "डेटा सुरक्षा में सेंध". This email goes to people whose data was affected, so the word matters. |
| 6 | `notice.basis_legitimate_use`, `notice.basis_legitimate_use_a` | "वैध उपयोग" for legitimate use | Check against the Hindi text of section 7. |
| 7 | `notice.withdraw_body`, `public.preferences.object` | "यह उपयोग रोकें" for the button "Stop this use" | A short button label. Does it read as an instruction to stop? |
| 8 | `public.rights.identifier_default`, `public.rights.identifier_body` | "साइन इन करने वाला ईमेल पता", then "कृपया :identifier बताएँ" | The sentence must still read well when an app sets its own identifier through `requests.identifier` (for example "आपका ग्राहक नंबर"). |
| 9 | `public.preferences.guardian_gives` | "नीचे दी गई सहमति आपके माता-पिता या अभिभावक द्वारा दी जाती है।" | The sentence says the consent below is given by the parent or guardian once they have confirmed. Check that the meaning is clear to a child reading it. |
| 10 | `public.statuses.rejected` | "संभव नहीं" for "Not possible" | A status label on a request. Is it polite enough, or is "अस्वीकृत" better? |
| 11 | `public.requests.due`, `public.requests.due_label` | "जवाब की अंतिम तिथि" | "तिथि" or "तारीख"? Which one do your readers use? |
| 12 | `public.preferences.legitimate_use` | "Act की धारा 7" | The word "Act" stays in English. The full name is in the notice. Do you want the full name here too? |
| 13 | `notice.version_line` | "संस्करण :version, :date को प्रकाशित" | The date is filled in by the code with Hindi month names and Western digits, for example "14 जनवरी 2027". Do you want Devanagari digits? That would be a code change. |
| 14 | `notice.heading`, `notice.intro` | "आपके व्यक्तिगत डेटा का :business द्वारा उपयोग" and "हम (:business)" | The text avoids verbs that depend on the gender of the business name. Check that both read naturally with a real business name. |
| 15 | `mail.erasure.logs` | "आपके डेटा के उपयोग के रिकॉर्ड (logs of processing)" | "लॉग" may be clearer to your readers than "रिकॉर्ड". |
| 16 | `public.nominations.*` | "नामित करें", "नामांकन", "हटाएँ" | Check "नामित" against the Hindi text of section 14. |
| 17 | the example purposes in `config/data-rights.php` | "उपयोग विश्लेषण (usage analytics)", "ईमेल से उत्पाद समाचार", "पासवर्ड (हैश के रूप में सहेजा गया)" | These are examples that every app replaces. Check they read naturally. |
| 18 | `public.preferences.contact_heading` | "सवाल" for Questions | "प्रश्न" is more formal. |
| 19 | loan words | "अपडेट", "साइन इन", "ईमेल", "फ़ोन", "डाउनलोड", "JSON" | Common English words are written in Devanagari where most readers use them. "अपडेट" could be "अद्यतन". |

Once a fluent reader has checked the Hindi, change `status` in `lang/hi/meta.php` from `needs_review` to `ready`.
