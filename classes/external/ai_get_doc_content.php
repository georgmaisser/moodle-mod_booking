<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External service: load a booking/docs markdown file and return it as rendered HTML.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\external;

use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_booking\local\wbagent\authorization_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Read one booking/docs markdown file and return it as safe HTML.
 *
 * The path is resolved strictly inside the mod_booking/docs directory;
 * any traversal attempt results in an error response.
 *
 * @package    mod_booking
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_get_doc_content extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course-module id.'),
            'path' => new external_value(PARAM_PATH, 'Relative path inside booking/docs, e.g. booking_rules/README.md'),
        ]);
    }

    /**
     * Load and render a documentation markdown file.
     *
     * @param int    $cmid
     * @param string $path  Relative path inside booking/docs.
     * @return array{success:bool, html:string, title:string, error:string}
     */
    public static function execute(int $cmid, string $path): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid, 'path' => $path]);

        $authz = new authorization_service();
        $authz->require_valid_context($params['cmid']);
        $context = context_module::instance($params['cmid']);
        self::validate_context($context);
        $authz->require_use_capability((int)$USER->id, $params['cmid']);

        $docsroot = realpath(dirname(__DIR__, 2) . '/docs');
        if ($docsroot === false || !is_dir($docsroot)) {
            return ['success' => false, 'html' => '', 'title' => '', 'error' => 'docs directory not found'];
        }

        // Resolve the requested path strictly inside docs root — prevent any traversal.
        $requested = realpath($docsroot . DIRECTORY_SEPARATOR . $params['path']);
        if (
            $requested === false
            || !is_file($requested)
            || strpos($requested, $docsroot) !== 0
            || strtolower(pathinfo($requested, PATHINFO_EXTENSION)) !== 'md'
        ) {
            return ['success' => false, 'html' => '', 'title' => '', 'error' => 'file not found or not accessible'];
        }

        $markdown = file_get_contents($requested);
        if ($markdown === false) {
            return ['success' => false, 'html' => '', 'title' => '', 'error' => 'could not read file'];
        }

        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            $title = trim($m[1]);
        }

        $html = self::markdown_to_html($markdown, $docsroot, $params['cmid']);

        return ['success' => true, 'html' => $html, 'title' => $title, 'error' => ''];
    }

    /**
     * Describe the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the file was loaded successfully.'),
            'html'    => new external_value(PARAM_RAW, 'Rendered HTML content of the markdown file.'),
            'title'   => new external_value(PARAM_TEXT, 'H1 title extracted from the document.'),
            'error'   => new external_value(PARAM_TEXT, 'Error message if success=false, otherwise empty.'),
        ]);
    }

    /**
     * Convert markdown to safe HTML.
     *
     * Handles the subset used in booking/docs:
     *   - H1–H4 headings
     *   - **bold** and *italic*
     *   - inline code spans and fenced code blocks
     *   - [text](url) links (external urls open in new tab; relative .md links rewritten to webservice calls)
     *   - unordered lists (-, *)
     *   - ordered lists (1. 2. …)
     *   - tables (basic GFM pipe tables)
     *   - horizontal rules (---)
     *   - blank-line-separated paragraphs
     *
     * All output is passed through htmlspecialchars where necessary so that
     * raw markdown content can never inject script tags.
     *
     * @param  string $markdown
     * @param  string $docsroot  Absolute path to the docs directory (unused here, kept for future extensions).
     * @param  int    $cmid
     * @return string  Safe HTML.
     */
    private static function markdown_to_html(string $markdown, string $docsroot, int $cmid): string {
        // Normalise line endings.
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Remove YAML front-matter if present.
        $text = preg_replace('/^---\n.*?\n---\n/s', '', $text) ?? $text;

        $lines  = explode("\n", $text);
        $html   = '';
        $i      = 0;
        $total  = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // Fenced code block (opening marker is three backtick characters).
            $backtick3 = str_repeat(chr(96), 3);
            if (preg_match('/^' . $backtick3 . '(\w*)/', $line, $m)) {
                $lang  = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $code  = '';
                $i++;
                while ($i < $total && !str_starts_with($lines[$i], $backtick3)) {
                    $code .= htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8') . "\n";
                    $i++;
                }
                $html .= '<pre class="booking-doc-code"><code' . ($lang ? " class=\"language-{$lang}\"" : '') . '>'
                    . $code . '</code></pre>' . "\n";
                $i++;
                continue;
            }

            // GFM pipe table: starts with |.
            if (str_starts_with(trim($line), '|') && isset($lines[$i + 1]) && str_starts_with(trim($lines[$i + 1]), '|')) {
                $tablehtml = '<table class="table table-sm table-bordered booking-doc-table">' . "\n";
                $isfirst   = true;
                while ($i < $total && str_starts_with(trim($lines[$i]), '|')) {
                    $cells = preg_split('/\s*\|\s*/', trim($lines[$i], '| ')) ?: [];
                    // Skip separator row (contains only dashes and colons).
                    if (preg_match('/^[\-\s:]+$/', implode('', $cells))) {
                        $i++;
                        $isfirst = false;
                        continue;
                    }
                    $tag = $isfirst ? 'th' : 'td';
                    $tablehtml .= '<tr>';
                    foreach ($cells as $cell) {
                        $tablehtml .= "<{$tag}>" . self::inline_format($cell, $cmid) . "</{$tag}>";
                    }
                    $tablehtml .= '</tr>' . "\n";
                    $isfirst = false;
                    $i++;
                }
                $html .= $tablehtml . '</table>' . "\n";
                continue;
            }

            // Headings.
            if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $m)) {
                $level   = strlen($m[1]);
                $content = self::inline_format($m[2], $cmid);
                $id      = 'doc-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($content)));
                $html   .= "<h{$level} id=\"{$id}\" class=\"booking-doc-h{$level}\">{$content}</h{$level}>\n";
                $i++;
                continue;
            }

            // Horizontal rule.
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', trim($line))) {
                $html .= "<hr>\n";
                $i++;
                continue;
            }

            // Unordered list.
            if (preg_match('/^(\s*)([-*])\s+(.+)$/', $line, $m)) {
                $html .= "<ul class=\"booking-doc-list\">\n";
                while ($i < $total && preg_match('/^(\s*)([-*])\s+(.+)$/', $lines[$i], $m)) {
                    $html .= '<li>' . self::inline_format($m[3], $cmid) . '</li>' . "\n";
                    $i++;
                }
                $html .= "</ul>\n";
                continue;
            }

            // Ordered list.
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
                $html .= "<ol class=\"booking-doc-list\">\n";
                while ($i < $total && preg_match('/^\d+\.\s+(.+)$/', $lines[$i], $m)) {
                    $html .= '<li>' . self::inline_format($m[1], $cmid) . '</li>' . "\n";
                    $i++;
                }
                $html .= "</ol>\n";
                continue;
            }

            // Blank line → paragraph break.
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraph: collect consecutive non-blank, non-special lines.
            $para = '';
            while (
                $i < $total
                && trim($lines[$i]) !== ''
                && !preg_match('/^#{1,4}\s|^' . str_repeat(chr(96), 3) . '|^[-*]\s|^\d+\.\s|^(\s*\|)/', $lines[$i])
            ) {
                $para .= ($para !== '' ? ' ' : '') . $lines[$i];
                $i++;
            }
            if ($para !== '') {
                $html .= '<p class="booking-doc-p">' . self::inline_format($para, $cmid) . "</p>\n";
            }
        }

        return $html;
    }

    /**
     * Apply inline markdown formatting to a single line or inline fragment.
     *
     * Handles: bold (**), italic (*), inline code (backtick), [text](url) links.
     * Relative .md links are rewritten to a JS-friendly data-docpath attribute
     * so the frontend can load them via the webservice instead of navigating away.
     *
     * @param  string $text
     * @param  int    $cmid
     * @return string HTML
     */
    private static function inline_format(string $text, int $cmid): string {
        // Links [text](url) — must run before escaping.
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            static function (array $m) use ($cmid): string {
                $label = htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8');
                $href  = trim($m[2]);

                // Relative .md path → render via webservice (data-docpath attribute).
                if (!preg_match('/^https?:\/\//', $href)) {
                    $safepath = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
                    return '<a href="#" class="booking-doc-link" data-docpath="' . $safepath . '" '
                        . 'data-cmid="' . (int)$cmid . '">' . $label . '</a>';
                }

                $safeurl = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
                return '<a href="' . $safeurl . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
            },
            $text
        ) ?? $text;

        // Escape remaining HTML (now that links are already safe tags).
        // Split on already-converted anchor tags; escape only the plain-text parts.
        $parts  = preg_split('/(<a [^>]+>.*?<\/a>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $output = '';
        foreach ($parts as $idx => $part) {
            if ($idx % 2 === 0) {
                // Text part — apply escaping + inline formatting.
                $part = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
                // Bold.
                $part = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $part) ?? $part;
                // Italic.
                $part = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $part) ?? $part;
                // Inline code spans delimited by single backtick characters.
                $backtick1 = chr(96);
                $codepattern = '/' . $backtick1 . '([^' . $backtick1 . ']+)' . $backtick1 . '/';
                $part = preg_replace($codepattern, '<code>$1</code>', $part) ?? $part;
            }
            $output .= $part;
        }

        return $output;
    }
}
