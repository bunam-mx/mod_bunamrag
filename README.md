# B@RAG Content #

**B@RAG Content** (`mod_bunamrag`) is a Moodle activity module built for UNAM's Bachillerato
a Distancia. An instance of the activity is a *learning unit*: a hierarchy of **themes** and
**subthemes** whose content (text, video, audio, H5P, and interactive exercises) is authored
once per subtheme code but published in up to three parallel **levels** — *remedial*,
*fundamental*, and *enriquecimiento*. The plugin uses a pair of diagnostic quizzes (DX
Fundamental / DX Enriquecimiento) to resolve, per student and per subtheme, which of those
three levels they should actually see — so every student gets a personalized route through
the same unit without a teacher having to build three separate copies of it.

Content itself is largely authored outside Moodle (via a RAG / AI-agent pipeline) and pasted
into the plugin's own notebook-style content editor.

## Table of contents

- [Content hierarchy](#content-hierarchy)
- [Personalized trajectory](#personalized-trajectory)
- [Content block types](#content-block-types)
- [LaTeX / math support](#latex--math-support)
- [Interactive exercises](#interactive-exercises)
- [Results tracking and summary](#results-tracking-and-summary)
- [Settings](#settings)
- [Capabilities](#capabilities)
- [Data model](#data-model)
- [File overview](#file-overview)
- [Installing via uploaded ZIP file](#installing-via-uploaded-zip-file)
- [Installing manually](#installing-manually)
- [License](#license)

## Content hierarchy

```
bunamrag (one activity instance = one learning unit)
 └─ theme (e.g. "1.1")
     └─ subtheme (e.g. "1.1.1" — code shared across levels)
         ├─ subtheme row, level = remedial        ─┐ up to 3 rows per code,
         ├─ subtheme row, level = fundamental       │ published independently
         └─ subtheme row, level = enriquecimiento  ─┘ as content becomes ready
             └─ content blocks (ordered, one subtheme-level can have several)
```

A subtheme **code** (e.g. `1.1.1`) identifies the same pedagogical unit across its up to three
level variants. Each level variant is authored, saved, and published independently — a
subtheme can exist only as its *remedial* row while *fundamental* and *enriquecimiento* are
still being produced — and each one holds its own ordered list of content blocks.

Teachers manage this hierarchy from `view.php` via **Manage content**:

- `manage.php` — themes: add/edit/reorder/delete, link to its subthemes.
- `manage_theme.php` — subthemes of one theme, grouped by code across the three levels:
  add/edit/reorder/delete (reordering and deleting act on the whole code-group, across levels
  at once), link to one level's content blocks.
- `manage_content.php` — the content blocks of one specific subtheme-level, presented
  notebook-style (think Jupyter/Colab): each block is a "cell" with move up/down/delete/save
  controls, and a row of icon buttons that appears on hover between cells to insert a new
  block of a chosen type at that exact position.

## Personalized trajectory

Students never choose their level manually — `view.php` resolves it for them
(`classes/local/trajectory_resolver.php`), using their attempts at the two quizzes configured
on the activity (**DX Fundamental quiz** / **DX Enrichment quiz**, set in the activity
settings):

1. **No finished DX Fundamental attempt** → the student sees a notice and a link to the quiz
   instead of content.
2. **DX Fundamental answered perfectly** → the student is eligible for enrichment:
   - DX Enrichment also answered perfectly → every subtheme is shown at the
     *enriquecimiento* level.
   - Otherwise → every subtheme is shown at the *fundamental* level.
3. **DX Fundamental not perfect** → resolved **per subtheme**, from that subtheme's own
   question (slot) inside the DX Fundamental attempt: answered correctly → *fundamental*;
   answered incorrectly → *remedial*.

If the resolved level's content was not published yet for a given subtheme, the student falls
back to *fundamental*, then to *remedial*.

The resolved level per subtheme code is cached the first time it is computed
(`bunamrag_trajectory` table) so it is not recalculated on every page view. Every subtheme
page a student visits is logged (`bunamrag_log`), so a returning student is offered the choice
to continue where they left off or start over.

Each subtheme's **DX Fundamental slot** (`bunamrag_subtheme.dxfslot` — which question in the
DX Fundamental quiz corresponds to it) is computed automatically from the presentation order
of the content (theme order, then subtheme order) every time that order changes; teachers
never set it directly.

A configurable set of **roles can bypass this entirely** (see [Settings](#settings)): they get
a manual level switcher instead, to preview any level on demand without having attempted
either quiz.

## Content block types

Each content block (`bunamrag_content` row) has one `blocktype`:

| Type            | Authoring                                              | Rendered to students as |
|-----------------|---------------------------------------------------------|--------------------------|
| `textcontent`   | Markdown, via the vendored split-view editor (toolbar fully enabled) | `format_text()` with `FORMAT_MARKDOWN` |
| `videourl`      | A YouTube URL (any form: watch/share/embed link) + an optional plain-text **transcript**, written with a restricted-toolbar instance of the same Markdown editor | A responsive `embed-responsive-16by9` (Bootstrap 4) YouTube iframe, with a "Transcript" button opening a Bootstrap modal |
| `podcastaudio`  | An audio URL | an HTML5 `<audio>` player |
| `h5p`           | An embeddable URL | an iframe |
| `interactive`   | Hand-written JSON, validated against one of 4 sub-schemas | see [Interactive exercises](#interactive-exercises) |

`textcontent`, the video transcript, and every text field inside an `interactive` block
support LaTeX out of the box — see [LaTeX / math support](#latex--math-support) for where
exactly and how to turn it on.

## LaTeX / math support

Math typesetting is **not** something this plugin implements itself — it is entirely Moodle's
own `filter_mathjaxloader` filter, which the plugin's text already flows through. There is
nothing to install, but the filter has to be turned on and the two gotchas below are easy to
trip over.

### Turning it on

1. **Site administration → Plugins → Filters → Manage filters** → set **MathJax** to "On".
2. Purge caches (**Site administration → Development → Purge caches**) so the change takes
   effect immediately rather than after the next cache cycle.
3. By default, MathJax's own JS library loads from a CDN
   (`filter_mathjaxloader/httpsurl`, a site setting). If the server/browser combination has no
   route to that CDN, nothing will typeset even though everything above is configured
   correctly — self-host the library and point that setting at your own copy instead.

### Where it applies

Any field rendered through `format_text(..., FORMAT_MARKDOWN)` gets it automatically once the
filter is on — no extra plugin-side configuration per field:
- `textcontent` blocks (`view.php`).
- The video `videourl` transcript.
- Inside `interactive` blocks: `instructions`, dialog card `text`/`back.text`, the
  multiplechoice `question` and each option's `feedback`, and every `globalfeedback` message.

The plain-text fields that are deliberately **not** passed through the Markdown parser
(multiplechoice option *labels*, and the fillblanks/dragwords `text` body, see
[Interactive exercises](#interactive-exercises)) still get LaTeX: they are run through
Moodle's filter chain directly (`interactive_schema::filter_plain_text()`), just skipping
the Markdown-to-HTML step.

### Syntax

Use `\( ... \)` or `\[ ... \]` for inline/display math, or `$$ ... $$` for a display block.
**Plain single `$ ... $` is not recognized** by Moodle's default MathJax configuration.

### Two gotchas

- **Escape backslashes in JSON.** Every `interactive` field lives inside a JSON string, where
  a bare `\` is an escape character. Write `\\(x^2\\)` in the JSON — it decodes to the literal
  `\(x^2\)` MathJax expects. Forgetting this is the most common cause of "the content is not
  valid JSON" errors when adding math to an interactive block.
- **A formula can't straddle a `{{...}}` blank** in `fillblanks`/`dragwords`: that text is
  split into independent segments around each blank before filtering, so `\(x {{blank}} y\)`
  would have its delimiters filtered in two unmatched pieces. Keep the whole expression either
  fully before or fully after the blank.

### Why `interactive` blocks still work even though they're pre-compiled

`interactive` blocks are compiled to HTML once, at save time (see
[Interactive exercises](#interactive-exercises)) — but MathJax normally expects to scan the
*live* page as it's filtered. To make that work for HTML that was actually filtered minutes,
days, or months earlier, `view.php` and the `manage_content.php` preview both call
`interactive_schema::ensure_filters_ready()` when they echo the stored HTML, and the
`interactive_util` AMD module fires the `core_filters/events` "content updated" signal once
the block is in the DOM — telling the already-loaded MathJax to re-scan that specific
content. You don't need to do anything for this to work; it's mentioned here only so a report
of "math isn't rendering" can be told apart from "the filter isn't configured" (see above).

## Interactive exercises

`interactive` blocks are authored as raw JSON in a textarea (a type `<select>` preloads a
starting template for each of the 4 sub-types), validated and **compiled to HTML once, at
save time** (`classes/local/interactive_schema.php::render()`) — `view.php` never re-parses
the JSON on every visit, it just echoes the already-compiled `bunamrag_content.renderedhtml`.

### Shared fields

Three fields are shared by every sub-type and have **two independent ways to set them**: a
form field in `manage_content.php`, and a matching key inside the JSON. The form fields are
always available (nothing is ever removed because the JSON can also do it); when the JSON
declares the key, that value wins over whatever the form field holds. This is the same
precedence `"type"` has always had — the subtype `<select>` only preloads a template, the
JSON's own `"type"` is what actually gets saved regardless of what the dropdown shows.

| Field | Form control | JSON key | Notes |
|---|---|---|---|
| Title | text input | `"title"` (string) | Shown in the results summary. |
| Instructions | textarea | `"instructions"` (string) | Markdown, rendered right before the interactive itself (after the title, if any). |
| Include in results tracking | checkbox | `"trackresults"` (`true`/`false`) | Only meaningful for `multiplechoice`/`fillblanks`/`dragwords`. **Dialog cards are always untracked**, no matter what either the checkbox or the JSON say. |

```json
{
  "type": "fillblanks",
  "title": "Photosynthesis vocabulary",
  "instructions": "Read the paragraph and fill in each blank with the correct term.",
  "trackresults": true,
  "text": "..."
}
```

### Dialog cards (`dialogcards`)

A horizontally-scrolling row of flip cards. Media is referenced by external URL (no file
upload integration). Never tracked for results (see table above).

```json
{
  "type": "dialogcards",
  "title": "Key terms",
  "instructions": "Click a card to see its definition on the back.",
  "cards": [
    {
      "front": {
        "image": "https://example.com/chloroplast.png",
        "alt": "Diagram of a chloroplast",
        "text": "Where does photosynthesis happen?"
      },
      "back": {
        "text": "Inside the **chloroplast**, specifically in the thylakoid membrane."
      }
    },
    {
      "front": {
        "image": "https://example.com/leaf.jpg",
        "alt": "Close-up of a green leaf",
        "audio": "https://example.com/question2.mp3"
      },
      "back": {
        "image": "https://example.com/stomata.png",
        "text": "Through the **stomata**, tiny pores on the leaf surface.",
        "audio": "https://example.com/answer2.mp3"
      }
    }
  ]
}
```
Each card's `front.image` and `front.alt` are required (accessibility); `front.text`/
`front.audio` are optional. `back` needs at least one of `image`/`text`/`audio`.

### Multiple choice (`multiplechoice`)

One block, **several questions**. Each option is directly clickable: clicking it answers that
question immediately (right/wrong, feedback revealed, sibling options disabled) — so each
question is effectively single-answer. The block finishes — revealing `globalfeedback` and
recording the attempt — automatically, as soon as every question has been answered; there is
no separate "Check" button.

```json
{
  "type": "multiplechoice",
  "title": "Photosynthesis quiz",
  "instructions": "Answer each question. You'll see feedback right away.",
  "trackresults": true,
  "onebyone": true,
  "randomizequestions": true,
  "questions": [
    {
      "question": "Where does photosynthesis take place?",
      "options": [
        { "text": "Mitochondria", "correct": false, "feedback": "That's where respiration happens." },
        { "text": "Chloroplast", "correct": true, "feedback": "Correct!" }
      ]
    },
    {
      "question": "Which gas do plants absorb for photosynthesis?",
      "options": [
        { "text": "Oxygen", "correct": false, "feedback": "Oxygen is a product, not an input." },
        { "text": "Carbon dioxide", "correct": true, "feedback": "Correct, \\(CO_2\\) is one of the raw materials." },
        { "text": "Nitrogen", "correct": false, "feedback": "Nitrogen isn't used in this process." }
      ]
    }
  ],
  "globalfeedback": [
    { "from": 90, "to": 100, "feedback": "Excellent! You've mastered this topic." },
    { "from": 50, "to": 90, "feedback": "Good start, review what goes in and out of the process." },
    { "from": 0, "to": 50, "feedback": "Review the topic before moving on." }
  ]
}
```

- `onebyone: true` shows one question at a time with a "Continue" button after answering it;
  `false` shows every question at once, each independently answerable.
- `randomizequestions` shuffles question order; **option order within a question is always
  shuffled**, regardless. Both shuffles happen client-side, on every page load (not fixed at
  save time — unlike `dragwords`'s word bank, see below).
- `score = correct questions / total questions × 100`.
- Remember to escape literal backslashes in JSON-authored LaTeX: write `\\(CO_2\\)`, not
  `\(CO_2\)` — see [LaTeX / math support](#latex--math-support).

### Fill the blanks (`fillblanks`) / Drag the words (`dragwords`)

Same authoring schema, different student-facing interaction. Blanks are marked inline with
`{{option1|option2|...}}` — deliberately **not** `*asterisks*`, to avoid colliding with
Markdown italics (this field is plain text, not Markdown, precisely so `{{...}}` is safe).
Both finish when the student presses the block's **"Check"** button (unlike `multiplechoice`,
which finishes automatically).

```json
{
  "type": "fillblanks",
  "title": "Fill in the process",
  "instructions": "Complete the paragraph below.",
  "trackresults": true,
  "text": "The process is called {{photosynthesis}} and needs {{CO2|carbon dioxide}} and {{water|H2O}}. It happens in the {{chloroplast|chloroplasts}}.",
  "casesensitive": false,
  "globalfeedback": [
    { "from": 100, "to": 100, "feedback": "Perfect!" },
    { "from": 0, "to": 100, "feedback": "Review the process and try again." }
  ]
}
```

```json
{
  "type": "dragwords",
  "title": "Drag the missing words",
  "instructions": "Drag each word into the blank where it belongs.",
  "trackresults": true,
  "text": "A {{CO2}} molecule enters the leaf, and together with water it is converted into {{glucose}} and {{oxygen}}. The leftover {{CO2}} that is not absorbed escapes back into the air.",
  "casesensitive": false,
  "globalfeedback": [
    { "from": 80, "to": 100, "feedback": "Great job!" },
    { "from": 0, "to": 80, "feedback": "Try again, check which word fits where." }
  ]
}
```

- `fillblanks` renders each blank as an inline `<input>`; the student's answer is compared
  against the `|`-separated alternatives (respecting `casesensitive`).
- `dragwords` renders each blank as a drop target and generates one draggable chip per blank
  (using its first alternative as the chip's label, shuffled into a word bank). A chip can be
  placed in **any** blank whose alternatives include that chip's text, not only "its own" —
  so in the example above, both `{{CO2}}` blanks accept either of the two "CO2" chips
  generated. Supports pointer/touch drag-and-drop *and* a fully keyboard-operable alternative
  (select a chip, then select a blank to place it; select again to pick it back up). Its word
  bank's shuffle order is fixed **at save time**, not re-randomized per student visit — unlike
  `multiplechoice`'s shuffling, which is done live in the browser.
- `score = correct blanks / total blanks × 100`.

### Shared pieces

- **`globalfeedback`** (multiplechoice, fillblanks, dragwords): an ordered list of score
  ranges (`from`/`to`, inclusive, 0–100); the **first** range containing the score wins, so put
  more specific/higher ranges first when ranges share a boundary (as in the multiplechoice
  example above, where 90–100 is listed before the overlapping 50–90).
- **LaTeX** works in every text field here, question/feedback included — see
  [LaTeX / math support](#latex--math-support) for where exactly, how to turn it on, and the
  JSON backslash-escaping gotcha.

## Results tracking and summary

Any `multiplechoice`/`fillblanks`/`dragwords` block can be marked **"include in results
tracking"** at authoring time. Every time a student finishes one, their attempt count and best
score (0–100) are recorded (`bunamrag_interactive_progress`, one row per student per block).

The activity settings' **Results summary** fieldset controls whether/how students see a
summary of their tracked exercises:

- **Show results summary after**: *Never* / *Each subtheme* / *Each theme* / *End of the
  activity*.
- **Custom message**: optional Markdown shown together with the summary.
- **Show the summary as**: a *modal* window, or an *independent page* (`summary.php`).
  - Modal + **"Open the modal automatically"**: clicking "Next" on the pager at the summary
    point does not navigate away — it opens the modal over the current page; its own
    "Continue" button is what actually advances.
  - Independent page + **"Show it as part of the pager"**: clicking "Next" at the summary
    point lands on the summary page first (an unnumbered stop, not counted in the pager), whose
    "Continue" button advances to the real next page.
  - With both of those switches off, the summary is only reachable through the explicit
    **"View results summary"** button — normal pager navigation is never interrupted.

The summary itself lists every tracked block in scope, including ones not attempted yet
("Not attempted yet"), limited to the levels the student's own trajectory actually resolved
to (it only considers subthemes the student has a `bunamrag_log` row for).

## Settings

**Site administration** (`settings.php`): **Roles that bypass the diagnostics** — users
holding one of the selected roles get a manual level switcher on `view.php` instead of the
personalized trajectory.

**Activity settings** (`mod_form.php`):
- **DX Fundamental quiz** / **DX Enrichment quiz** — which quiz activities in the course drive
  the trajectory.
- **Results summary** fieldset — see [above](#results-tracking-and-summary).

## Capabilities

| Capability            | Default roles                    | Purpose |
|------------------------|-----------------------------------|---------|
| `mod/bunamrag:view`    | student, teacher, manager, guest | View the activity and record interactive attempts. |
| `mod/bunamrag:manage`  | editingteacher, manager           | Manage themes/subthemes/content blocks. |
| `mod/bunamrag:addinstance` | editingteacher, manager       | Add the activity to a course. |

## Data model

| Table | Purpose |
|---|---|
| `bunamrag` | One row per activity instance: name/intro, the two DX quiz links, and the results-summary settings. |
| `bunamrag_theme` | Themes of a unit. |
| `bunamrag_subtheme` | Subthemes — one row per level per code; carries the auto-computed `dxfslot`. |
| `bunamrag_content` | Content blocks of one subtheme-level; generic `content` payload (HTML/Markdown/URL/JSON depending on `blocktype`), plus `title`/`trackresults`/`interactivetype`/`renderedhtml` for interactive blocks. |
| `bunamrag_trajectory` | Cached resolved level per student per subtheme code. |
| `bunamrag_log` | Every subtheme view, for resume-where-you-left-off and for scoping the results summary. |
| `bunamrag_interactive_progress` | Per-student attempt count and best score on a tracked interactive block. |

## File overview

```
view.php              Student-facing view: trajectory resolution, pagination, results summary.
summary.php           Independent results-summary page.
manage.php            Manage themes.
manage_theme.php      Manage subthemes of one theme.
manage_content.php    Manage content blocks of one subtheme-level (notebook-style editor).
mod_form.php          Activity settings form.
settings.php          Site admin settings (bypass roles).
lib.php               Standard module callbacks + bunamrag_resequence_dxfslots(), bunamrag_youtube_video_id().
classes/local/        trajectory_resolver, interactive_schema, results_summary.
classes/external/     record_interactive_attempt (AJAX web service).
classes/event/        Standard Moodle events.
classes/privacy/      Privacy API provider.
amd/src/, amd/build/  markdowneditor, manage_interactive, interactive_* (one per sub-type) + interactive_util, results_summary.
thirdpartylibs/       Vendored markdown-text-editor library (see thirdpartylibs.xml).
db/                   install.xml, upgrade.php, access.php, services.php.
```

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/bunamrag

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2026 Omar Terrazas Razo <terrazasrazo@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
