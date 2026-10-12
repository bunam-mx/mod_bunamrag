<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Single source of truth for the "interactive" content block: the JSON
 * templates offered to the teacher, validation of the JSON they author, and
 * compiling that JSON to the HTML actually shown to students.
 *
 * Compilation happens once, when the teacher saves (see manage_content.php),
 * not on every view.php visit - the result is cached in
 * bunamrag_content.renderedhtml.
 *
 * @package     mod_bunamrag
 * @copyright   2026 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunamrag\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates and renders mod_bunamrag "interactive" blocks.
 */
class interactive_schema {

    /** @var string[] the supported interactive subtypes. */
    const TYPES = ['dialogcards', 'multiplechoice', 'fillblanks', 'dragwords'];

    /**
     * JSON templates offered to the teacher when they pick a subtype for a
     * new (or emptied) interactive block.
     *
     * @return array type => pretty-printed JSON string.
     */
    public static function templates(): array {
        $dialogcards = [
            'type' => 'dialogcards',
            'instructions' => '',
            'cards' => [
                [
                    'front' => ['image' => 'https://...', 'alt' => 'Descripción de la imagen', 'text' => '', 'audio' => ''],
                    'back' => ['image' => '', 'text' => 'Texto de respaldo del reverso', 'audio' => ''],
                ],
            ],
        ];

        $multiplechoice = [
            'type' => 'multiplechoice',
            'instructions' => '',
            'trackresults' => false,
            'onebyone' => false,
            'randomizequestions' => false,
            'questions' => [
                [
                    'question' => 'Escribe la primera pregunta aquí',
                    'options' => [
                        ['text' => 'Opción 1', 'correct' => true, 'feedback' => ''],
                        ['text' => 'Opción 2', 'correct' => false, 'feedback' => ''],
                    ],
                ],
                [
                    'question' => 'Escribe la segunda pregunta aquí',
                    'options' => [
                        ['text' => 'Opción 1', 'correct' => false, 'feedback' => ''],
                        ['text' => 'Opción 2', 'correct' => true, 'feedback' => ''],
                    ],
                ],
            ],
            'globalfeedback' => [
                ['from' => 90, 'to' => 100, 'feedback' => '¡Excelente!'],
                ['from' => 0, 'to' => 90, 'feedback' => 'Sigue practicando.'],
            ],
        ];

        $blanktext = [
            'type' => 'fillblanks',
            'instructions' => '',
            'trackresults' => false,
            'text' => 'Escribe el texto con espacios como {{respuesta|alternativa}} aquí.',
            'casesensitive' => false,
            'globalfeedback' => [
                ['from' => 90, 'to' => 100, 'feedback' => '¡Excelente!'],
                ['from' => 0, 'to' => 90, 'feedback' => 'Sigue practicando.'],
            ],
        ];

        $dragwords = $blanktext;
        $dragwords['type'] = 'dragwords';

        return [
            'dialogcards' => json_encode($dialogcards, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'multiplechoice' => json_encode($multiplechoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'fillblanks' => json_encode($blanktext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'dragwords' => json_encode($dragwords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Validate decoded JSON for the given subtype.
     *
     * @param string $type
     * @param mixed $decoded result of json_decode($content).
     * @return string[] error messages, empty if valid.
     */
    public static function validate(string $type, $decoded): array {
        if (!is_object($decoded)) {
            return [get_string('interactive_error_invalidjson', 'mod_bunamrag')];
        }

        $sharederrors = self::validate_shared_fields($decoded);

        switch ($type) {
            case 'dialogcards':
                return array_merge($sharederrors, self::validate_dialogcards($decoded));
            case 'multiplechoice':
                return array_merge($sharederrors, self::validate_multiplechoice($decoded));
            case 'fillblanks':
            case 'dragwords':
                return array_merge($sharederrors, self::validate_blanktext($decoded));
            default:
                return array_merge(
                    $sharederrors,
                    [get_string('interactive_error_missingtype', 'mod_bunamrag', implode(', ', self::TYPES))]
                );
        }
    }

    /**
     * Validate the optional fields shared by every subtype: "instructions"
     * (shown before the interactive renders) and "trackresults" (whether to
     * include this block in the results summary). Both can also be set from
     * the manage_content.php form instead - when present here, the JSON
     * value takes precedence (see manage_content.php's savecontent action).
     *
     * @param \stdClass $decoded
     * @return string[]
     */
    private static function validate_shared_fields(\stdClass $decoded): array {
        $errors = [];

        if (property_exists($decoded, 'instructions')
            && $decoded->instructions !== null
            && !is_string($decoded->instructions)
        ) {
            $errors[] = get_string('interactive_error_instructions', 'mod_bunamrag');
        }

        if (property_exists($decoded, 'trackresults') && !is_bool($decoded->trackresults)) {
            $errors[] = get_string('interactive_error_trackresults', 'mod_bunamrag');
        }

        return $errors;
    }

    /**
     * Compile decoded JSON to the HTML shown to students. Only ever called
     * with already-validated data, and only once, at save time - the result
     * is cached in bunamrag_content.renderedhtml.
     *
     * This does NOT enqueue the matching AMD module: it runs during the
     * savecontent POST handler, whose response is always discarded by a
     * redirect(), so nothing queued here would ever reach a rendered page.
     * Callers that echo the stored renderedhtml (view.php, and the preview
     * in manage_content.php) must call js_call_amd('mod_bunamrag/interactive_'
     * . $block->interactivetype, 'init') themselves.
     *
     * @param string $type
     * @param \stdClass $decoded
     * @param \context $context used to format_text() markdown fields.
     * @param int $contentid the bunamrag_content id, embedded so tracked
     *            blocks can report attempts back against it.
     * @param bool $trackresults whether to embed the data attribute that
     *             makes the AMD module record attempts. Always false for
     *             dialogcards, which are never tracked.
     * @param string $title optional label shown above the block and used to
     *               identify it in the results summary.
     * @param string $instructions optional markdown shown above the block,
     *               below the title, before the interactive itself.
     * @return string HTML.
     */
    public static function render(
        string $type,
        \stdClass $decoded,
        \context $context,
        int $contentid = 0,
        bool $trackresults = false,
        string $title = '',
        string $instructions = ''
    ): string {
        switch ($type) {
            case 'dialogcards':
                $inner = self::render_dialogcards($decoded, $context);
                break;
            case 'multiplechoice':
                $inner = self::render_multiplechoice($decoded, $context, $contentid, $trackresults);
                break;
            case 'fillblanks':
                $inner = self::render_blanktext($decoded, $context, false, $contentid, $trackresults);
                break;
            case 'dragwords':
                $inner = self::render_blanktext($decoded, $context, true, $contentid, $trackresults);
                break;
            default:
                return '';
        }

        $prefix = '';
        if (trim($title) !== '') {
            $prefix .= \html_writer::tag('h4', format_string($title), ['class' => 'bunamrag-interactive-title']);
        }
        if (trim($instructions) !== '') {
            $prefix .= \html_writer::div(
                format_text($instructions, FORMAT_MARKDOWN, ['context' => $context]),
                'bunamrag-interactive-instructions'
            );
        }

        return $prefix . $inner;
    }

    /**
     * Validate a dialogcards payload.
     *
     * @param \stdClass $decoded
     * @return string[]
     */
    private static function validate_dialogcards(\stdClass $decoded): array {
        $errors = [];

        if (empty($decoded->cards) || !is_array($decoded->cards)) {
            $errors[] = get_string('interactive_error_dialogcards_nocards', 'mod_bunamrag');
            return $errors;
        }

        foreach ($decoded->cards as $index => $card) {
            $n = $index + 1;
            $front = is_object($card) ? ($card->front ?? null) : null;
            $back = is_object($card) ? ($card->back ?? null) : null;

            if (!is_object($front) || empty($front->image) || empty($front->alt)) {
                $errors[] = get_string('interactive_error_dialogcards_card', 'mod_bunamrag', $n);
            }

            if (!is_object($back) || (empty($back->image) && empty($back->text) && empty($back->audio))) {
                $errors[] = get_string('interactive_error_dialogcards_back', 'mod_bunamrag', $n);
            }
        }

        return $errors;
    }

    /**
     * Validate a multiplechoice payload.
     *
     * @param \stdClass $decoded
     * @return string[]
     */
    private static function validate_multiplechoice(\stdClass $decoded): array {
        $errors = [];

        $questions = is_array($decoded->questions ?? null) ? $decoded->questions : [];
        if (empty($questions)) {
            $errors[] = get_string('interactive_error_multiplechoice_noquestions', 'mod_bunamrag');
            return $errors;
        }

        foreach ($questions as $qindex => $question) {
            $qn = $qindex + 1;

            if (!is_object($question) || empty($question->question) || trim((string) $question->question) === '') {
                $errors[] = get_string('interactive_error_multiplechoice_question', 'mod_bunamrag', $qn);
            }

            $options = is_object($question) && is_array($question->options ?? null) ? $question->options : [];
            if (count($options) < 2) {
                $errors[] = get_string('interactive_error_multiplechoice_options', 'mod_bunamrag', $qn);
            }

            $hascorrect = false;
            foreach ($options as $oindex => $option) {
                if (!is_object($option) || empty($option->text) || trim((string) $option->text) === '') {
                    $errors[] = get_string('interactive_error_multiplechoice_optiontext', 'mod_bunamrag', (object) [
                        'qindex' => $qn, 'oindex' => $oindex + 1,
                    ]);
                }
                if (is_object($option) && !empty($option->correct)) {
                    $hascorrect = true;
                }
            }
            if (!$hascorrect) {
                $errors[] = get_string('interactive_error_multiplechoice_correct', 'mod_bunamrag', $qn);
            }
        }

        return array_merge($errors, self::validate_globalfeedback($decoded));
    }

    /**
     * Validate a fillblanks/dragwords payload.
     *
     * @param \stdClass $decoded
     * @return string[]
     */
    private static function validate_blanktext(\stdClass $decoded): array {
        $errors = [];

        $text = (string) ($decoded->text ?? '');
        if (trim($text) === '') {
            $errors[] = get_string('interactive_error_blanktext_empty', 'mod_bunamrag');
            return $errors;
        }

        $segments = self::parse_blank_text($text);
        $blanks = array_filter($segments, fn($s) => $s['type'] === 'blank');

        if (empty($blanks)) {
            $errors[] = get_string('interactive_error_blanktext_noblanks', 'mod_bunamrag');
        }

        foreach ($blanks as $index => $blank) {
            if (empty($blank['alternatives'])) {
                $errors[] = get_string('interactive_error_blanktext_emptyoption', 'mod_bunamrag', $index + 1);
            }
        }

        return array_merge($errors, self::validate_globalfeedback($decoded));
    }

    /**
     * Validate the shared "globalfeedback" score-range array, when present.
     *
     * @param \stdClass $decoded
     * @return string[]
     */
    private static function validate_globalfeedback(\stdClass $decoded): array {
        $errors = [];

        if (empty($decoded->globalfeedback)) {
            return $errors;
        }

        if (!is_array($decoded->globalfeedback)) {
            $errors[] = get_string('interactive_error_globalfeedback', 'mod_bunamrag', 1);
            return $errors;
        }

        foreach ($decoded->globalfeedback as $index => $range) {
            $valid = is_object($range)
                && isset($range->from) && is_numeric($range->from)
                && isset($range->to) && is_numeric($range->to)
                && !empty($range->feedback) && trim((string) $range->feedback) !== '';
            if (!$valid) {
                $errors[] = get_string('interactive_error_globalfeedback', 'mod_bunamrag', $index + 1);
            }
        }

        return $errors;
    }

    /**
     * Split a fillblanks/dragwords "text" into ordered text/blank segments.
     *
     * @param string $text
     * @return array list of ['type' => 'text', 'value' => string]
     *               or ['type' => 'blank', 'alternatives' => string[]]
     */
    private static function parse_blank_text(string $text): array {
        $parts = preg_split('/\{\{(.*?)\}\}/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $segments = [];
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                if ($part !== '') {
                    $segments[] = ['type' => 'text', 'value' => $part];
                }
            } else {
                $alternatives = array_values(array_filter(
                    array_map('trim', explode('|', $part)),
                    fn($alt) => $alt !== ''
                ));
                $segments[] = ['type' => 'blank', 'alternatives' => $alternatives];
            }
        }

        return $segments;
    }

    /**
     * Let enabled content filters (e.g. MathJax) set up whatever they need
     * on the CURRENT page (typically loading their JS) - e.g. so that
     * \(x^2\)-style LaTeX embedded in a pre-compiled interactive block
     * actually gets typeset by MathJax when the block is displayed.
     *
     * Must be called from the page that echoes renderedhtml (view.php,
     * and the manage_content.php preview), not from render() itself: render()
     * runs during the savecontent POST handler, whose response is always
     * discarded by a redirect(), so anything it queues onto $PAGE would
     * never reach a rendered page (see the render() docblock).
     *
     * Safe to call more than once per page.
     *
     * @param \context $context
     */
    public static function ensure_filters_ready(\context $context): void {
        global $PAGE;
        \filter_manager::instance()->setup_page_for_filters($PAGE, $context);
    }

    /**
     * Escape a plain-text fragment and run it through the filter chain
     * (MathJax, etc.), without running it through format_text()'s
     * markdown/HTML parsing - used for the fillblanks/dragwords body, which
     * is plain text by design.
     *
     * @param string $text
     * @param \context $context
     * @return string
     */
    private static function filter_plain_text(string $text, \context $context): string {
        return \filter_manager::instance()->filter_text(s($text), $context);
    }

    /**
     * Render the shared "globalfeedback" ranges as a JSON blob, with each
     * feedback message pre-rendered to HTML, for the AMD module to consume.
     *
     * @param \stdClass $decoded
     * @param \context $context
     * @return string JSON.
     */
    private static function render_globalfeedback_json(\stdClass $decoded, \context $context): string {
        $ranges = [];
        foreach (($decoded->globalfeedback ?? []) as $range) {
            $ranges[] = [
                'from' => (float) $range->from,
                'to' => (float) $range->to,
                'feedback' => format_text((string) $range->feedback, FORMAT_MARKDOWN, ['context' => $context]),
            ];
        }
        // Slashes are deliberately left escaped (no JSON_UNESCAPED_SLASHES):
        // this JSON is embedded inside a <script> tag, and an unescaped
        // "</script" inside a feedback message would terminate it early.
        return json_encode($ranges, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Render a dialogcards payload.
     *
     * @param \stdClass $decoded
     * @param \context $context
     * @return string
     */
    private static function render_dialogcards(\stdClass $decoded, \context $context): string {
        $cards = [];
        foreach ($decoded->cards as $card) {
            $cards[] = \html_writer::div(
                self::render_dialogcard_face($card->front, $context, 'front') .
                self::render_dialogcard_face($card->back, $context, 'back'),
                'bunamrag-dialogcard'
            );
        }

        return \html_writer::div(
            \html_writer::div(implode('', $cards), 'bunamrag-dialogcards-track'),
            'bunamrag-dialogcards',
            ['data-bunamrag-interactive' => 'dialogcards']
        );
    }

    /**
     * Render one face (front or back) of a dialog card.
     *
     * @param \stdClass|null $face
     * @param \context $context
     * @param string $side 'front' or 'back'.
     * @return string
     */
    private static function render_dialogcard_face(?\stdClass $face, \context $context, string $side): string {
        $face = $face ?? new \stdClass();
        $hidden = $side === 'back' ? ['hidden' => 'hidden'] : [];
        $content = '';

        if (!empty($face->image)) {
            $content .= \html_writer::empty_tag('img', [
                'src' => $face->image,
                'alt' => $face->alt ?? '',
                'class' => 'bunamrag-dialogcard-image',
            ]);
        }
        if (!empty($face->text)) {
            $content .= \html_writer::div(
                format_text((string) $face->text, FORMAT_MARKDOWN, ['context' => $context]),
                'bunamrag-dialogcard-text'
            );
        }
        if (!empty($face->audio)) {
            $content .= \html_writer::tag('audio', '', ['src' => $face->audio, 'controls' => 'controls']);
        }

        $label = $side === 'front'
            ? get_string('interactive_dialogcards_showback', 'mod_bunamrag')
            : get_string('interactive_dialogcards_showfront', 'mod_bunamrag');
        $content .= \html_writer::tag('button', $label, [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary bunamrag-dialogcard-flip',
        ]);

        return \html_writer::div($content, 'bunamrag-dialogcard-face bunamrag-dialogcard-' . $side, $hidden);
    }

    /**
     * Build the data-* attributes that tell the AMD module whether (and
     * against which bunamrag_content id / course module) to record attempts.
     *
     * @param int $contentid
     * @param bool $trackresults
     * @param \context $context a context_module; its instanceid is the cmid
     *                 the record-attempt web service needs.
     * @return array
     */
    private static function tracking_attrs(int $contentid, bool $trackresults, \context $context): array {
        if (!$trackresults || $contentid <= 0) {
            return [];
        }
        return [
            'data-bunamrag-contentid' => $contentid,
            'data-bunamrag-cmid' => $context->instanceid,
            'data-bunamrag-track' => '1',
        ];
    }

    /**
     * Render a multiplechoice payload.
     *
     * @param \stdClass $decoded
     * @param \context $context
     * @param int $contentid
     * @param bool $trackresults
     * @return string
     */
    private static function render_multiplechoice(
        \stdClass $decoded, \context $context, int $contentid = 0, bool $trackresults = false
    ): string {
        $onebyone = !empty($decoded->onebyone);
        $randomizequestions = !empty($decoded->randomizequestions);

        $questionshtml = '';
        foreach ($decoded->questions as $qindex => $question) {
            $optionshtml = '';
            foreach ($question->options as $option) {
                $feedback = '';
                if (!empty($option->feedback)) {
                    $feedback = \html_writer::div(
                        format_text((string) $option->feedback, FORMAT_MARKDOWN, ['context' => $context]),
                        'bunamrag-mc-feedback',
                        ['hidden' => 'hidden']
                    );
                }

                $optionshtml .= \html_writer::tag(
                    'button',
                    \html_writer::tag('span', self::filter_plain_text((string) $option->text, $context)) . $feedback,
                    [
                        'type' => 'button',
                        'class' => 'bunamrag-mc-option',
                        'data-correct' => !empty($option->correct) ? '1' : '0',
                    ]
                );
            }

            $continuebutton = '';
            if ($onebyone) {
                $continuebutton = \html_writer::tag('button', get_string('interactive_continue', 'mod_bunamrag'), [
                    'type' => 'button',
                    'class' => 'btn btn-primary btn-sm mt-2 bunamrag-mc-continue',
                    'hidden' => 'hidden',
                ]);
            }

            $qblockattrs = ['data-index' => $qindex];
            if ($onebyone && $qindex > 0) {
                $qblockattrs['hidden'] = 'hidden';
            }

            $questionshtml .= \html_writer::div(
                \html_writer::div(
                    format_text((string) $question->question, FORMAT_MARKDOWN, ['context' => $context]),
                    'bunamrag-mc-question'
                ) .
                \html_writer::div($optionshtml, 'bunamrag-mc-options') .
                $continuebutton,
                'bunamrag-mc-qblock',
                $qblockattrs
            );
        }

        $globalfeedback = \html_writer::div('', 'bunamrag-mc-globalfeedback mt-2', ['aria-live' => 'polite', 'hidden' => 'hidden']);
        $globalfeedbackdata = \html_writer::tag(
            'script', self::render_globalfeedback_json($decoded, $context),
            ['type' => 'application/json', 'class' => 'bunamrag-mc-globalfeedback-data']
        );

        return \html_writer::div(
            $questionshtml . $globalfeedback . $globalfeedbackdata,
            'bunamrag-mc',
            [
                'data-bunamrag-interactive' => 'multiplechoice',
                'data-onebyone' => $onebyone ? '1' : '0',
                'data-randomizequestions' => $randomizequestions ? '1' : '0',
            ] + self::tracking_attrs($contentid, $trackresults, $context)
        );
    }

    /**
     * Render a fillblanks or dragwords payload.
     *
     * @param \stdClass $decoded
     * @param \context $context
     * @param bool $dragwords true for the drag-the-words variant.
     * @param int $contentid
     * @param bool $trackresults
     * @return string
     */
    private static function render_blanktext(
        \stdClass $decoded, \context $context, bool $dragwords, int $contentid = 0, bool $trackresults = false
    ): string {
        $segments = self::parse_blank_text((string) $decoded->text);
        $casesensitive = !empty($decoded->casesensitive) ? '1' : '0';

        $body = '';
        $chips = [];
        $blankindex = 0;

        foreach ($segments as $segment) {
            if ($segment['type'] === 'text') {
                // Deliberately not format_text(): the fillblanks/dragwords
                // body is plain text (see the class docblock on why markdown
                // would collide with the {{...}} blank syntax). Still run it
                // through the filter chain on top of the escaped text so
                // things like the MathJax filter keep working here too.
                $body .= self::filter_plain_text($segment['value'], $context);
                continue;
            }

            $blankindex++;
            $alternatives = implode('|', $segment['alternatives']);

            if ($dragwords) {
                $body .= \html_writer::tag('span', '', [
                    'class' => 'bunamrag-dropzone',
                    'data-alternatives' => $alternatives,
                    'data-casesensitive' => $casesensitive,
                    'data-index' => $blankindex,
                    'tabindex' => '0',
                    'role' => 'button',
                    'aria-label' => get_string('interactive_dragwords_emptyslot', 'mod_bunamrag', $blankindex),
                ]);
                $chips[] = \html_writer::tag('span', s($segment['alternatives'][0]), [
                    'class' => 'bunamrag-dragchip',
                    'draggable' => 'true',
                    'tabindex' => '0',
                    'role' => 'button',
                    'data-text' => $segment['alternatives'][0],
                ]);
            } else {
                $body .= \html_writer::empty_tag('input', [
                    'type' => 'text',
                    'class' => 'bunamrag-fillblank',
                    'data-alternatives' => $alternatives,
                    'data-casesensitive' => $casesensitive,
                    'aria-label' => get_string('interactive_fillblanks_blank', 'mod_bunamrag', $blankindex),
                    'size' => max(6, mb_strlen($segment['alternatives'][0] ?? '') + 2),
                ]);
            }
        }

        $checkbutton = \html_writer::tag('button', get_string('interactive_check', 'mod_bunamrag'), [
            'type' => 'button',
            'class' => 'btn btn-primary btn-sm mt-2 bunamrag-blanktext-check',
        ]);

        $globalfeedback = \html_writer::div(
            '', 'bunamrag-blanktext-globalfeedback mt-2', ['aria-live' => 'polite', 'hidden' => 'hidden']
        );
        $globalfeedbackdata = \html_writer::tag(
            'script', self::render_globalfeedback_json($decoded, $context),
            ['type' => 'application/json', 'class' => 'bunamrag-blanktext-globalfeedback-data']
        );

        $extra = '';
        if ($dragwords) {
            shuffle($chips);
            $extra = \html_writer::div(implode('', $chips), 'bunamrag-dragwords-bank', [
                'aria-label' => get_string('interactive_dragwords_bank', 'mod_bunamrag'),
            ]);
        }

        return \html_writer::div(
            $extra .
            \html_writer::div(nl2br($body), 'bunamrag-blanktext-body') .
            $checkbutton . $globalfeedback . $globalfeedbackdata,
            $dragwords ? 'bunamrag-dragwords' : 'bunamrag-fillblanks',
            ['data-bunamrag-interactive' => $dragwords ? 'dragwords' : 'fillblanks']
                + self::tracking_attrs($contentid, $trackresults, $context)
        );
    }
}
