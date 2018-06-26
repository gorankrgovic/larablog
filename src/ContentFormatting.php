<?php
namespace Gorankrgovic\Larablog;

use Illuminate\Support\Facades\App;

/**
 * Class ContentFormatting
 * @package Gorankrgovic\Larablog
 */
class ContentFormatting
{

    /**
     * Sanitizes a title, or returns a fallback title.
     *
     * Specifically, HTML and PHP tags are stripped. Further actions can be added
     * via the plugin API. If $title is empty and $fallback_title is set, the latter
     * will be used.
     *
     * @since 1.0.0
     *
     * @param string $title          The string to be sanitized.
     * @param string $fallback_title Optional. A title to use if $title is empty.
     * @param string $context        Optional. The operation for which the string is sanitized
     * @return string The sanitized string.
     */
    public static function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
        $raw_title = $title;
        if ( 'save' == $context ) {
            $title = self::remove_accents( $title );
        }
        /**
         * Filters a sanitized title string.
         *
         * @since 1.2.0
         *
         * @param string $title     Sanitized title.
         * @param string $raw_title The title prior to sanitization.
         * @param string $context   The context for which the title is being sanitized.
         */

        if ( '' === $title || false === $title ) {
            $title = $fallback_title;
        }
        return $title;
    }


    /**
     * @param $title
     * @param string $raw_title
     * @param string $context
     * @return mixed|string
     */
    public static function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) {
        $title = strip_tags( $title );
        // Preserve escaped octets.
        $title = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title );
        // Remove percent signs that are not part of an octet.
        $title = str_replace( '%', '', $title );
        // Restore octets.
        $title = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title );
        if ( self::seems_utf8( $title ) ) {
            if ( function_exists( 'mb_strtolower' ) ) {
                $title = mb_strtolower( $title, 'UTF-8' );
            }
        }
        $title = strtolower( $title );
        if ( 'save' == $context ) {
            // Convert nbsp, ndash and mdash to hyphens
            $title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
            // Convert nbsp, ndash and mdash HTML entities to hyphens
            $title = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $title );
            // Convert forward slash to hyphen
            $title = str_replace( '/', '-', $title );
            // Strip these characters entirely
            $title = str_replace(
                array(
                    // iexcl and iquest
                    '%c2%a1',
                    '%c2%bf',
                    // angle quotes
                    '%c2%ab',
                    '%c2%bb',
                    '%e2%80%b9',
                    '%e2%80%ba',
                    // curly quotes
                    '%e2%80%98',
                    '%e2%80%99',
                    '%e2%80%9c',
                    '%e2%80%9d',
                    '%e2%80%9a',
                    '%e2%80%9b',
                    '%e2%80%9e',
                    '%e2%80%9f',
                    // copy, reg, deg, hellip and trade
                    '%c2%a9',
                    '%c2%ae',
                    '%c2%b0',
                    '%e2%80%a6',
                    '%e2%84%a2',
                    // acute accents
                    '%c2%b4',
                    '%cb%8a',
                    '%cc%81',
                    '%cd%81',
                    // grave accent, macron, caron
                    '%cc%80',
                    '%cc%84',
                    '%cc%8c',
                ), '', $title
            );
            // Convert times to x
            $title = str_replace( '%c3%97', 'x', $title );
        }
        $title = preg_replace( '/&.+?;/', '', $title ); // kill entities
        $title = str_replace( '.', '-', $title );
        $title = preg_replace( '/[^%a-z0-9 _-]/', '', $title );
        $title = preg_replace( '/\s+/', '-', $title );
        $title = preg_replace( '|-+|', '-', $title );
        $title = trim( $title, '-' );
        return $title;
    }

    /**
     * @param $value
     * @return mixed
     */
    public static function _unslash( $value ) {
        return self::stripslashes_deep( $value );
    }

    /**
     * Navigates through an array, object, or scalar, and removes slashes from the values.
     *
     * @since 2.0.0
     *
     * @param mixed $value The value to be stripped.
     * @return mixed Stripped value.
     */
    public static function stripslashes_deep( $value ) {
        return self::map_deep( $value, 'self::stripslashes_from_strings_only' );
    }

    /**
     * @param $value
     * @return string
     */
    public static function stripslashes_from_strings_only( $value ) {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }

    /**
     * @param $value
     * @param $callback
     * @return array|mixed
     */
    public static function map_deep( $value, $callback ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $index => $item ) {
                $value[ $index ] = self::map_deep( $item, $callback );
            }
        } elseif ( is_object( $value ) ) {
            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->$property_name = self::map_deep( $property_value, $callback );
            }
        } else {
            $value = call_user_func( $callback, $value );
        }

        return $value;
    }

    /**
     * Do the opposite of wpautop
     *
     * @param string $s
     *
     * @return string
     */
    public static function reverse_autop( $s ) {
        //remove any new lines already in there
        $s = str_replace( "\n", '', $s );
        //replace <br /> with \n
        $s = str_replace( [ '<br />', '<br>', '<br/>'], "\n", $s );
        //replace </p> with \n\n
        $s = str_replace( '</p>', "\n\n", $s );
        // Remove all characters from beginning and end of line
        return self::filter_html( $s );
    }

    /**
     * Function to remove all unwanted tags from the html and thus keeping it clean.
     *
     * @param $input
     *
     * @param string $add_to_allowed_tag
     * @return array|mixed|string
     */
    public static function filter_html( $input, $add_to_allowed_tag = '' ) {
        // God WordPress this is not very kind
        $value = self::_unslash( $input );
        // Remove empty paragraph and create
        $value = str_replace( [ '&nbsp;', '<p>&nbsp;</p>' ], [ '', '' ], $value );
        $value = str_replace( [ '<p></p>' ], [ '' ], $value );
        $value = str_replace( [ '<b>', '</b>' ], [ '<strong>', '</strong>' ], $value );
        $value = str_replace( [ '<i>', '</i>' ], [ '<em>', '</em>' ], $value );
        // Remove unallowed tags
        $allowed_tags = '<a><strong><em><ol><ul><li>' . $add_to_allowed_tag;
        $value        = strip_tags( $value, $allowed_tags );
        /**
         * Little hack to remove all attributes in an html string
         */
        $dom = new \DOMDocument;
        libxml_use_internal_errors( true );
        /**
         * This is to have DOMDocument interpret the string as UTF-8
         */
        $dom->loadHTML( '<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $value );
        $xpath = new \DOMXPath( $dom );
        $nodes = $xpath->query( '//@*' );
        foreach ( $nodes as $node ) {
            $parent = $node->parentNode;
            if ( ! is_object( $parent ) ) {
                continue;
            }
            /**
             * We want to keep anchor attributes
             */
            if ( 'a' === $parent->tagName && $parent->hasAttributes() ) {
                $allowed_attrs = [
                    'title',
                    'target',
                    'href',
                ];
                for ( $i = $parent->attributes->length - 1; $i >= 0; --$i ) {
                    if ( ! in_array( $parent->attributes->item( 0 )->name, $allowed_attrs ) ) {
                        $parent->removeAttributeNode( $parent->attributes->item( $i ) );
                    }
                }
            } else {
                $node->parentNode->removeAttribute( $node->nodeName );
            }
        }
        /**
         * Make sure DOMDocument didn't introduce any more unallowed tags like <body>
         */
        $value = strip_tags( $dom->saveHTML(), $allowed_tags );
        libxml_clear_errors();
        return trim( $value );
    }

    /**
     * @return string
     */
    public static function get_html_split_regex() {
        static $regex;

        if ( ! isset( $regex ) ) {
            $comments =
                '!'           // Start of comment, after the <.
                . '(?:'         // Unroll the loop: Consume everything until --> is found.
                .     '-(?!->)' // Dash not followed by end of comment.
                .     '[^\-]*+' // Consume non-dashes.
                . ')*+'         // Loop possessively.
                . '(?:-->)?';   // End of comment. If not found, match all input.

            $cdata =
                '!\[CDATA\['  // Start of comment, after the <.
                . '[^\]]*+'     // Consume non-].
                . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
                .     '](?!]>)' // One ] not followed by end of comment.
                .     '[^\]]*+' // Consume non-].
                . ')*+'         // Loop possessively.
                . '(?:]]>)?';   // End of comment. If not found, match all input.

            $escaped =
                '(?='           // Is the element escaped?
                .    '!--'
                . '|'
                .    '!\[CDATA\['
                . ')'
                . '(?(?=!-)'      // If yes, which type?
                .     $comments
                . '|'
                .     $cdata
                . ')';

            $regex =
                '/('              // Capture the entire match.
                .     '<'           // Find start of element.
                .     '(?'          // Conditional expression follows.
                .         $escaped  // Find end of escaped element.
                .     '|'           // ... else ...
                .         '[^>]*>?' // Find end of normal element.
                .     ')'
                . ')/';
        }

        return $regex;
    }

    /**
     * @param $input
     * @return array|false|string[]
     */
    private static function _html_split( $input ) {
        return preg_split( self::get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
    }

    /**
     * @param $haystack
     * @param $replace_pairs
     * @return string
     */
    private static function _replace_in_html_tags( $haystack, $replace_pairs ) {
        // Find all elements.
        $textarr = self::_html_split( $haystack );
        $changed = false;

        // Optimize when searching for one item.
        if ( 1 === count( $replace_pairs ) ) {
            // Extract $needle and $replace.
            foreach ( $replace_pairs as $needle => $replace );

            // Loop through delimiters (elements) only.
            for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
                if ( false !== strpos( $textarr[$i], $needle ) ) {
                    $textarr[$i] = str_replace( $needle, $replace, $textarr[$i] );
                    $changed = true;
                }
            }
        } else {
            // Extract all $needles.
            $needles = array_keys( $replace_pairs );

            // Loop through delimiters (elements) only.
            for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
                foreach ( $needles as $needle ) {
                    if ( false !== strpos( $textarr[$i], $needle ) ) {
                        $textarr[$i] = strtr( $textarr[$i], $replace_pairs );
                        $changed = true;
                        // After one strtr() break out of the foreach loop and look at next element.
                        break;
                    }
                }
            }
        }

        if ( $changed ) {
            $haystack = implode( $textarr );
        }

        return $haystack;
    }


    /**
     * Replaces double line-breaks with paragraph elements.
     *
     * A group of regex replaces used to identify text formatted with newlines and
     * replace double line-breaks with HTML paragraph tags. The remaining line-breaks
     * after conversion become <<br />> tags, unless $br is set to '0' or 'false'.
     *
     * @since 0.71
     *
     * @param string $pee The text which has to be formatted.
     * @param bool   $br  Optional. If set, this will convert all remaining line-breaks
     *                    after paragraphing. Default true.
     * @return string Text which has been converted into correct paragraph tags.
     */
    public static function autop( $pee, $br = true ) {
        $pre_tags = array();

        if ( trim($pee) === '' )
            return '';

        // Just to make things a little easier, pad the end.
        $pee = $pee . "\n";

        /*
         * Pre tags shouldn't be touched by autop.
         * Replace pre tags with placeholders and bring them back after autop.
         */
        if ( strpos($pee, '<pre') !== false ) {
            $pee_parts = explode( '</pre>', $pee );
            $last_pee = array_pop($pee_parts);
            $pee = '';
            $i = 0;

            foreach ( $pee_parts as $pee_part ) {
                $start = strpos($pee_part, '<pre');

                // Malformed html?
                if ( $start === false ) {
                    $pee .= $pee_part;
                    continue;
                }

                $name = "<pre pre-tag-$i></pre>";
                $pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

                $pee .= substr( $pee_part, 0, $start ) . $name;
                $i++;
            }

            $pee .= $last_pee;
        }
        // Change multiple <br>s into two line breaks, which will turn into paragraphs.
        $pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);

        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

        // Add a double line break above block-level opening tags.
        $pee = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee);

        // Add a double line break below block-level closing tags.
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);

        // Standardize newline characters to "\n".
        $pee = str_replace(array("\r\n", "\r"), "\n", $pee);

        // Find newlines in all elements and add placeholders.
        $pee = self::_replace_in_html_tags( $pee, array( "\n" => " <!-- wpnl --> " ) );

        // Collapse line breaks before and after <option> elements so they don't get autop'd.
        if ( strpos( $pee, '<option' ) !== false ) {
            $pee = preg_replace( '|\s*<option|', '<option', $pee );
            $pee = preg_replace( '|</option>\s*|', '</option>', $pee );
        }

        /*
         * Collapse line breaks inside <object> elements, before <param> and <embed> elements
         * so they don't get autop'd.
         */
        if ( strpos( $pee, '</object>' ) !== false ) {
            $pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
            $pee = preg_replace( '|\s*</object>|', '</object>', $pee );
            $pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
        }

        /*
         * Collapse line breaks inside <audio> and <video> elements,
         * before and after <source> and <track> elements.
         */
        if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
            $pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
            $pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
            $pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
        }

        // Collapse line breaks before and after <figcaption> elements.
        if ( strpos( $pee, '<figcaption' ) !== false ) {
            $pee = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $pee );
            $pee = preg_replace( '|</figcaption>\s*|', '</figcaption>', $pee );
        }

        // Remove more than two contiguous line breaks.
        $pee = preg_replace("/\n\n+/", "\n\n", $pee);

        // Split up the contents into an array of strings, separated by double line breaks.
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);

        // Reset $pee prior to rebuilding.
        $pee = '';

        // Rebuild the content as a string, wrapping every bit with a <p>.
        foreach ( $pees as $tinkle ) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }

        // Under certain strange conditions it could create a P of entirely whitespace.
        $pee = preg_replace('|<p>\s*</p>|', '', $pee);

        // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);

        // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

        // In some cases <li> may get wrapped in <p>, fix them.
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee);

        // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);

        // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
        $pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);

        // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

        // Optionally insert line breaks.
        if ( $br ) {
            // Replace newlines that shouldn't be touched with a placeholder.
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', 'self::_autop_newline_preservation_helper', $pee);

            // Normalize <br>e
            $pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );

            // Replace any new line characters that aren't preceded by a <br /> with a <br />.
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);

            // Replace newline placeholders with newlines.
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }

        // If a <br /> tag is after an opening or closing block tag, remove it.
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);

        // If a <br /> tag is before a subset of opening or closing block tags, remove it.
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        $pee = preg_replace( "|\n</p>$|", '</p>', $pee );

        // Replace placeholder <pre> tags with their original content.
        if ( !empty($pre_tags) )
            $pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

        // Restore newlines in all elements.
        if ( false !== strpos( $pee, '<!-- wpnl -->' ) ) {
            $pee = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $pee );
        }

        return $pee;
    }



    /**
     * Extract and return the first URL from passed content.
     *
     * @since 3.6.0
     *
     * @param string $content A string which might contain a URL.
     * @return string|false The found URL.
     */
    public static function get_url_in_content( $content ) {
        if ( empty( $content ) ) {
            return false;
        }

        if ( preg_match( '/<a\s[^>]*?href=([\'"])(.+?)\1/is', $content, $matches ) ) {
            return self::esc_url_raw( $matches[2] );
        }

        return false;
    }


    /**
     * Performs esc_url() for database usage.
     *
     * @since 2.8.0
     *
     * @param string $url       The URL to be cleaned.
     * @param array  $protocols An array of acceptable protocols.
     * @return string The cleaned URL.
     */
    public static function esc_url_raw( $url, $protocols = null ) {
        return self::esc_url( $url, $protocols, 'db' );
    }


    /**
     * Checks and cleans a URL.
     *
     * A number of characters are removed from the URL. If the URL is for displaying
     * (the default behaviour) ampersands are also replaced. The {@see 'clean_url'} filter
     * is applied to the returned cleaned URL.
     *
     * @since 2.8.0
     *
     * @param string $url       The URL to be cleaned.
     * @param array  $protocols Optional. An array of acceptable protocols.
     *		                    Defaults to return value of wp_allowed_protocols()
     * @param string $_context  Private. Use esc_url_raw() for database usage.
     * @return string The cleaned $url after the {@see 'clean_url'} filter is applied.
     */
    public static function esc_url( $url, $protocols = null, $_context = 'display' ) {
        $original_url = $url;

        if ( '' == $url )
            return $url;

        $url = str_replace( ' ', '%20', $url );
        $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);

        if ( '' === $url ) {
            return $url;
        }

        if ( 0 !== stripos( $url, 'mailto:' ) ) {
            $strip = array('%0d', '%0a', '%0D', '%0A');
            $url = self::_deep_replace($strip, $url);
        }

        $url = str_replace(';//', '://', $url);
        /* If the URL doesn't appear to contain a scheme, we
         * presume it needs http:// prepended (unless a relative
         * link starting with /, # or ? or a php file).
         */
        if ( strpos($url, ':') === false && ! in_array( $url[0], array( '/', '#', '?' ) ) &&
            ! preg_match('/^[a-z0-9-]+?\.php/i', $url) )
            $url = 'http://' . $url;

        // Replace ampersands and single quotes only when displaying.
        if ( 'display' == $_context ) {
            $url = self::kses_normalize_entities( $url );
            $url = str_replace( '&amp;', '&#038;', $url );
            $url = str_replace( "'", '&#039;', $url );
        }

        if ( ( false !== strpos( $url, '[' ) ) || ( false !== strpos( $url, ']' ) ) ) {

            $parsed = self::_parse_url( $url );
            $front  = '';

            if ( isset( $parsed['scheme'] ) ) {
                $front .= $parsed['scheme'] . '://';
            } elseif ( '/' === $url[0] ) {
                $front .= '//';
            }

            if ( isset( $parsed['user'] ) ) {
                $front .= $parsed['user'];
            }

            if ( isset( $parsed['pass'] ) ) {
                $front .= ':' . $parsed['pass'];
            }

            if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
                $front .= '@';
            }

            if ( isset( $parsed['host'] ) ) {
                $front .= $parsed['host'];
            }

            if ( isset( $parsed['port'] ) ) {
                $front .= ':' . $parsed['port'];
            }

            $end_dirty = str_replace( $front, '', $url );
            $end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
            $url       = str_replace( $end_dirty, $end_clean, $url );

        }

        if ( '/' === $url[0] ) {
            $good_protocol_url = $url;
        } else {
            if ( ! is_array( $protocols ) )
                $protocols = self::_allowed_protocols();
            $good_protocol_url = self::_kses_bad_protocol( $url, $protocols );
            if ( strtolower( $good_protocol_url ) != strtolower( $url ) )
                return '';
        }


        /**
         * Filters a string cleaned and escaped for output as a URL.
         *
         * @since 2.3.0
         *
         * @param string $good_protocol_url The cleaned URL to be returned.
         * @param string $original_url      The URL prior to cleaning.
         * @param string $_context          If 'display', replace ampersands and single quotes only.
         */
        return $good_protocol_url;
    }


    /**
     * Perform a deep string replace operation to ensure the values in $search are no longer present
     *
     * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
     * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
     * str_replace would return
     *
     * @since 2.8.1
     * @access private
     *
     * @param string|array $search  The value being searched for, otherwise known as the needle.
     *                              An array may be used to designate multiple needles.
     * @param string       $subject The string being searched and replaced on, otherwise known as the haystack.
     * @return string The string with the replaced svalues.
     */
    public static function _deep_replace( $search, $subject ) {
        $subject = (string) $subject;

        $count = 1;
        while ( $count ) {
            $subject = str_replace( $search, '', $subject, $count );
        }

        return $subject;
    }

    /**
     * Newline preservation help function for wpautop
     *
     * @since 3.1.0
     * @access private
     *
     * @param array $matches preg_replace_callback matches array
     * @return string
     */
    public static function _autop_newline_preservation_helper( $matches ) {
        return str_replace( "\n", "<WPPreserveNewline />", $matches[0] );
    }


    /**
     * @param $string
     * @return mixed
     */
    public static function kses_normalize_entities($string) {
        // Disarm all entities by converting & to &amp;
        $string = str_replace('&', '&amp;', $string);

        // Change back the allowed entities in our entity whitelist
        $string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', '_kses_named_entities', $string);
        $string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', '_kses_normalize_entities2', $string);
        $string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', '_kses_normalize_entities3', $string);

        return $string;
    }


    /**
     * @param $url
     * @param int $component
     * @return mixed|null
     */
    public static function _parse_url( $url, $component = -1 ) {
        $to_unset = array();
        $url = strval( $url );

        if ( '//' === substr( $url, 0, 2 ) ) {
            $to_unset[] = 'scheme';
            $url = 'placeholder:' . $url;
        } elseif ( '/' === substr( $url, 0, 1 ) ) {
            $to_unset[] = 'scheme';
            $to_unset[] = 'host';
            $url = 'placeholder://placeholder' . $url;
        }

        $parts = @parse_url( $url );

        if ( false === $parts ) {
            // Parsing failure.
            return $parts;
        }

        // Remove the placeholder values.
        foreach ( $to_unset as $key ) {
            unset( $parts[ $key ] );
        }

        return self::_get_component_from_parsed_url_array( $parts, $component );
    }


    /**
     * @param $url_parts
     * @param int $component
     * @return mixed|null
     */
    public static function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
        if ( -1 === $component ) {
            return $url_parts;
        }

        $key = self::_translate_php_url_constant_to_key( $component );
        if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
            return $url_parts[ $key ];
        } else {
            return null;
        }
    }

    /**
     * @param $constant
     * @return bool|mixed
     */
    public static function _translate_php_url_constant_to_key( $constant ) {
        $translation = array(
            PHP_URL_SCHEME   => 'scheme',
            PHP_URL_HOST     => 'host',
            PHP_URL_PORT     => 'port',
            PHP_URL_USER     => 'user',
            PHP_URL_PASS     => 'pass',
            PHP_URL_PATH     => 'path',
            PHP_URL_QUERY    => 'query',
            PHP_URL_FRAGMENT => 'fragment',
        );

        if ( isset( $translation[ $constant ] ) ) {
            return $translation[ $constant ];
        } else {
            return false;
        }
    }


    /**
     * @return array
     */
    public static function _allowed_protocols() {
        static $protocols = array();

        if ( empty( $protocols ) ) {
            $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
        }
        return $protocols;
    }

    /**
     * @param $string
     * @param $allowed_protocols
     * @return mixed|string
     */
    public static function _kses_bad_protocol($string, $allowed_protocols) {
        $string = self::_kses_no_null($string);
        $iterations = 0;

        do {
            $original_string = $string;
            $string = self::_kses_bad_protocol_once($string, $allowed_protocols);
        } while ( $original_string != $string && ++$iterations < 6 );

        if ( $original_string != $string )
            return '';

        return $string;
    }

    /**
     * @param $string
     * @param null $options
     * @return mixed
     */
    public static function _kses_no_null( $string, $options = null ) {
        if ( ! isset( $options['slash_zero'] ) ) {
            $options = array( 'slash_zero' => 'remove' );
        }

        $string = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string );
        if ( 'remove' == $options['slash_zero'] ) {
            $string = preg_replace( '/\\\\+0+/', '', $string );
        }

        return $string;
    }


    /**
     * @param $string
     * @param $allowed_protocols
     * @param int $count
     * @return string
     */
    public static function _kses_bad_protocol_once($string, $allowed_protocols, $count = 1 ) {
        $string2 = preg_split( '/:|&#0*58;|&#x0*3a;/i', $string, 2 );
        if ( isset($string2[1]) && ! preg_match('%/\?%', $string2[0]) ) {
            $string = trim( $string2[1] );
            $protocol = self::_kses_bad_protocol_once2( $string2[0], $allowed_protocols );
            if ( 'feed:' == $protocol ) {
                if ( $count > 2 )
                    return '';
                $string = self::_kses_bad_protocol_once( $string, $allowed_protocols, ++$count );
                if ( empty( $string ) )
                    return $string;
            }
            $string = $protocol . $string;
        }

        return $string;
    }

    /**
     * @param $string
     * @param $allowed_protocols
     * @return string
     */
    public static function _kses_bad_protocol_once2( $string, $allowed_protocols ) {
        $string2 = self::_kses_decode_entities($string);
        $string2 = preg_replace('/\s/', '', $string2);
        $string2 = self::_kses_no_null($string2);
        $string2 = strtolower($string2);

        $allowed = false;
        foreach ( (array) $allowed_protocols as $one_protocol )
            if ( strtolower($one_protocol) == $string2 ) {
                $allowed = true;
                break;
            }

        if ($allowed)
            return "$string2:";
        else
            return '';
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function _kses_decode_entities($string) {
        $string = preg_replace_callback('/&#([0-9]+);/', 'self::_kses_decode_entities_chr', $string);
        $string = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', 'self::_kses_decode_entities_chr_hexdec', $string);

        return $string;
    }

    /**
     * @param $match
     * @return string
     */
    public static function _kses_decode_entities_chr( $match ) {
        return chr( $match[1] );
    }

    /**
     * @param $match
     * @return string
     */
    public static function _kses_decode_entities_chr_hexdec( $match ) {
        return chr( hexdec( $match[1] ) );
    }


    /**
     * Converts all accent characters to ASCII characters.
     *
     * If there are no accent characters, then the string given is just returned.
     *
     * **Accent characters converted:**
     *
     * Currency signs:
     *
     * |   Code   | Glyph | Replacement |     Description     |
     * | -------- | ----- | ----------- | ------------------- |
     * | U+00A3   | £     | (empty)     | British Pound sign  |
     * | U+20AC   | €     | E           | Euro sign           |
     *
     * Decompositions for Latin-1 Supplement:
     *
     * |  Code   | Glyph | Replacement |               Description              |
     * | ------- | ----- | ----------- | -------------------------------------- |
     * | U+00AA  | ª     | a           | Feminine ordinal indicator             |
     * | U+00BA  | º     | o           | Masculine ordinal indicator            |
     * | U+00C0  | À     | A           | Latin capital letter A with grave      |
     * | U+00C1  | Á     | A           | Latin capital letter A with acute      |
     * | U+00C2  | Â     | A           | Latin capital letter A with circumflex |
     * | U+00C3  | Ã     | A           | Latin capital letter A with tilde      |
     * | U+00C4  | Ä     | A           | Latin capital letter A with diaeresis  |
     * | U+00C5  | Å     | A           | Latin capital letter A with ring above |
     * | U+00C6  | Æ     | AE          | Latin capital letter AE                |
     * | U+00C7  | Ç     | C           | Latin capital letter C with cedilla    |
     * | U+00C8  | È     | E           | Latin capital letter E with grave      |
     * | U+00C9  | É     | E           | Latin capital letter E with acute      |
     * | U+00CA  | Ê     | E           | Latin capital letter E with circumflex |
     * | U+00CB  | Ë     | E           | Latin capital letter E with diaeresis  |
     * | U+00CC  | Ì     | I           | Latin capital letter I with grave      |
     * | U+00CD  | Í     | I           | Latin capital letter I with acute      |
     * | U+00CE  | Î     | I           | Latin capital letter I with circumflex |
     * | U+00CF  | Ï     | I           | Latin capital letter I with diaeresis  |
     * | U+00D0  | Ð     | D           | Latin capital letter Eth               |
     * | U+00D1  | Ñ     | N           | Latin capital letter N with tilde      |
     * | U+00D2  | Ò     | O           | Latin capital letter O with grave      |
     * | U+00D3  | Ó     | O           | Latin capital letter O with acute      |
     * | U+00D4  | Ô     | O           | Latin capital letter O with circumflex |
     * | U+00D5  | Õ     | O           | Latin capital letter O with tilde      |
     * | U+00D6  | Ö     | O           | Latin capital letter O with diaeresis  |
     * | U+00D8  | Ø     | O           | Latin capital letter O with stroke     |
     * | U+00D9  | Ù     | U           | Latin capital letter U with grave      |
     * | U+00DA  | Ú     | U           | Latin capital letter U with acute      |
     * | U+00DB  | Û     | U           | Latin capital letter U with circumflex |
     * | U+00DC  | Ü     | U           | Latin capital letter U with diaeresis  |
     * | U+00DD  | Ý     | Y           | Latin capital letter Y with acute      |
     * | U+00DE  | Þ     | TH          | Latin capital letter Thorn             |
     * | U+00DF  | ß     | s           | Latin small letter sharp s             |
     * | U+00E0  | à     | a           | Latin small letter a with grave        |
     * | U+00E1  | á     | a           | Latin small letter a with acute        |
     * | U+00E2  | â     | a           | Latin small letter a with circumflex   |
     * | U+00E3  | ã     | a           | Latin small letter a with tilde        |
     * | U+00E4  | ä     | a           | Latin small letter a with diaeresis    |
     * | U+00E5  | å     | a           | Latin small letter a with ring above   |
     * | U+00E6  | æ     | ae          | Latin small letter ae                  |
     * | U+00E7  | ç     | c           | Latin small letter c with cedilla      |
     * | U+00E8  | è     | e           | Latin small letter e with grave        |
     * | U+00E9  | é     | e           | Latin small letter e with acute        |
     * | U+00EA  | ê     | e           | Latin small letter e with circumflex   |
     * | U+00EB  | ë     | e           | Latin small letter e with diaeresis    |
     * | U+00EC  | ì     | i           | Latin small letter i with grave        |
     * | U+00ED  | í     | i           | Latin small letter i with acute        |
     * | U+00EE  | î     | i           | Latin small letter i with circumflex   |
     * | U+00EF  | ï     | i           | Latin small letter i with diaeresis    |
     * | U+00F0  | ð     | d           | Latin small letter Eth                 |
     * | U+00F1  | ñ     | n           | Latin small letter n with tilde        |
     * | U+00F2  | ò     | o           | Latin small letter o with grave        |
     * | U+00F3  | ó     | o           | Latin small letter o with acute        |
     * | U+00F4  | ô     | o           | Latin small letter o with circumflex   |
     * | U+00F5  | õ     | o           | Latin small letter o with tilde        |
     * | U+00F6  | ö     | o           | Latin small letter o with diaeresis    |
     * | U+00F8  | ø     | o           | Latin small letter o with stroke       |
     * | U+00F9  | ù     | u           | Latin small letter u with grave        |
     * | U+00FA  | ú     | u           | Latin small letter u with acute        |
     * | U+00FB  | û     | u           | Latin small letter u with circumflex   |
     * | U+00FC  | ü     | u           | Latin small letter u with diaeresis    |
     * | U+00FD  | ý     | y           | Latin small letter y with acute        |
     * | U+00FE  | þ     | th          | Latin small letter Thorn               |
     * | U+00FF  | ÿ     | y           | Latin small letter y with diaeresis    |
     *
     * Decompositions for Latin Extended-A:
     *
     * |  Code   | Glyph | Replacement |                    Description                    |
     * | ------- | ----- | ----------- | ------------------------------------------------- |
     * | U+0100  | Ā     | A           | Latin capital letter A with macron                |
     * | U+0101  | ā     | a           | Latin small letter a with macron                  |
     * | U+0102  | Ă     | A           | Latin capital letter A with breve                 |
     * | U+0103  | ă     | a           | Latin small letter a with breve                   |
     * | U+0104  | Ą     | A           | Latin capital letter A with ogonek                |
     * | U+0105  | ą     | a           | Latin small letter a with ogonek                  |
     * | U+01006 | Ć     | C           | Latin capital letter C with acute                 |
     * | U+0107  | ć     | c           | Latin small letter c with acute                   |
     * | U+0108  | Ĉ     | C           | Latin capital letter C with circumflex            |
     * | U+0109  | ĉ     | c           | Latin small letter c with circumflex              |
     * | U+010A  | Ċ     | C           | Latin capital letter C with dot above             |
     * | U+010B  | ċ     | c           | Latin small letter c with dot above               |
     * | U+010C  | Č     | C           | Latin capital letter C with caron                 |
     * | U+010D  | č     | c           | Latin small letter c with caron                   |
     * | U+010E  | Ď     | D           | Latin capital letter D with caron                 |
     * | U+010F  | ď     | d           | Latin small letter d with caron                   |
     * | U+0110  | Đ     | D           | Latin capital letter D with stroke                |
     * | U+0111  | đ     | d           | Latin small letter d with stroke                  |
     * | U+0112  | Ē     | E           | Latin capital letter E with macron                |
     * | U+0113  | ē     | e           | Latin small letter e with macron                  |
     * | U+0114  | Ĕ     | E           | Latin capital letter E with breve                 |
     * | U+0115  | ĕ     | e           | Latin small letter e with breve                   |
     * | U+0116  | Ė     | E           | Latin capital letter E with dot above             |
     * | U+0117  | ė     | e           | Latin small letter e with dot above               |
     * | U+0118  | Ę     | E           | Latin capital letter E with ogonek                |
     * | U+0119  | ę     | e           | Latin small letter e with ogonek                  |
     * | U+011A  | Ě     | E           | Latin capital letter E with caron                 |
     * | U+011B  | ě     | e           | Latin small letter e with caron                   |
     * | U+011C  | Ĝ     | G           | Latin capital letter G with circumflex            |
     * | U+011D  | ĝ     | g           | Latin small letter g with circumflex              |
     * | U+011E  | Ğ     | G           | Latin capital letter G with breve                 |
     * | U+011F  | ğ     | g           | Latin small letter g with breve                   |
     * | U+0120  | Ġ     | G           | Latin capital letter G with dot above             |
     * | U+0121  | ġ     | g           | Latin small letter g with dot above               |
     * | U+0122  | Ģ     | G           | Latin capital letter G with cedilla               |
     * | U+0123  | ģ     | g           | Latin small letter g with cedilla                 |
     * | U+0124  | Ĥ     | H           | Latin capital letter H with circumflex            |
     * | U+0125  | ĥ     | h           | Latin small letter h with circumflex              |
     * | U+0126  | Ħ     | H           | Latin capital letter H with stroke                |
     * | U+0127  | ħ     | h           | Latin small letter h with stroke                  |
     * | U+0128  | Ĩ     | I           | Latin capital letter I with tilde                 |
     * | U+0129  | ĩ     | i           | Latin small letter i with tilde                   |
     * | U+012A  | Ī     | I           | Latin capital letter I with macron                |
     * | U+012B  | ī     | i           | Latin small letter i with macron                  |
     * | U+012C  | Ĭ     | I           | Latin capital letter I with breve                 |
     * | U+012D  | ĭ     | i           | Latin small letter i with breve                   |
     * | U+012E  | Į     | I           | Latin capital letter I with ogonek                |
     * | U+012F  | į     | i           | Latin small letter i with ogonek                  |
     * | U+0130  | İ     | I           | Latin capital letter I with dot above             |
     * | U+0131  | ı     | i           | Latin small letter dotless i                      |
     * | U+0132  | Ĳ     | IJ          | Latin capital ligature IJ                         |
     * | U+0133  | ĳ     | ij          | Latin small ligature ij                           |
     * | U+0134  | Ĵ     | J           | Latin capital letter J with circumflex            |
     * | U+0135  | ĵ     | j           | Latin small letter j with circumflex              |
     * | U+0136  | Ķ     | K           | Latin capital letter K with cedilla               |
     * | U+0137  | ķ     | k           | Latin small letter k with cedilla                 |
     * | U+0138  | ĸ     | k           | Latin small letter Kra                            |
     * | U+0139  | Ĺ     | L           | Latin capital letter L with acute                 |
     * | U+013A  | ĺ     | l           | Latin small letter l with acute                   |
     * | U+013B  | Ļ     | L           | Latin capital letter L with cedilla               |
     * | U+013C  | ļ     | l           | Latin small letter l with cedilla                 |
     * | U+013D  | Ľ     | L           | Latin capital letter L with caron                 |
     * | U+013E  | ľ     | l           | Latin small letter l with caron                   |
     * | U+013F  | Ŀ     | L           | Latin capital letter L with middle dot            |
     * | U+0140  | ŀ     | l           | Latin small letter l with middle dot              |
     * | U+0141  | Ł     | L           | Latin capital letter L with stroke                |
     * | U+0142  | ł     | l           | Latin small letter l with stroke                  |
     * | U+0143  | Ń     | N           | Latin capital letter N with acute                 |
     * | U+0144  | ń     | n           | Latin small letter N with acute                   |
     * | U+0145  | Ņ     | N           | Latin capital letter N with cedilla               |
     * | U+0146  | ņ     | n           | Latin small letter n with cedilla                 |
     * | U+0147  | Ň     | N           | Latin capital letter N with caron                 |
     * | U+0148  | ň     | n           | Latin small letter n with caron                   |
     * | U+0149  | ŉ     | n           | Latin small letter n preceded by apostrophe       |
     * | U+014A  | Ŋ     | N           | Latin capital letter Eng                          |
     * | U+014B  | ŋ     | n           | Latin small letter Eng                            |
     * | U+014C  | Ō     | O           | Latin capital letter O with macron                |
     * | U+014D  | ō     | o           | Latin small letter o with macron                  |
     * | U+014E  | Ŏ     | O           | Latin capital letter O with breve                 |
     * | U+014F  | ŏ     | o           | Latin small letter o with breve                   |
     * | U+0150  | Ő     | O           | Latin capital letter O with double acute          |
     * | U+0151  | ő     | o           | Latin small letter o with double acute            |
     * | U+0152  | Œ     | OE          | Latin capital ligature OE                         |
     * | U+0153  | œ     | oe          | Latin small ligature oe                           |
     * | U+0154  | Ŕ     | R           | Latin capital letter R with acute                 |
     * | U+0155  | ŕ     | r           | Latin small letter r with acute                   |
     * | U+0156  | Ŗ     | R           | Latin capital letter R with cedilla               |
     * | U+0157  | ŗ     | r           | Latin small letter r with cedilla                 |
     * | U+0158  | Ř     | R           | Latin capital letter R with caron                 |
     * | U+0159  | ř     | r           | Latin small letter r with caron                   |
     * | U+015A  | Ś     | S           | Latin capital letter S with acute                 |
     * | U+015B  | ś     | s           | Latin small letter s with acute                   |
     * | U+015C  | Ŝ     | S           | Latin capital letter S with circumflex            |
     * | U+015D  | ŝ     | s           | Latin small letter s with circumflex              |
     * | U+015E  | Ş     | S           | Latin capital letter S with cedilla               |
     * | U+015F  | ş     | s           | Latin small letter s with cedilla                 |
     * | U+0160  | Š     | S           | Latin capital letter S with caron                 |
     * | U+0161  | š     | s           | Latin small letter s with caron                   |
     * | U+0162  | Ţ     | T           | Latin capital letter T with cedilla               |
     * | U+0163  | ţ     | t           | Latin small letter t with cedilla                 |
     * | U+0164  | Ť     | T           | Latin capital letter T with caron                 |
     * | U+0165  | ť     | t           | Latin small letter t with caron                   |
     * | U+0166  | Ŧ     | T           | Latin capital letter T with stroke                |
     * | U+0167  | ŧ     | t           | Latin small letter t with stroke                  |
     * | U+0168  | Ũ     | U           | Latin capital letter U with tilde                 |
     * | U+0169  | ũ     | u           | Latin small letter u with tilde                   |
     * | U+016A  | Ū     | U           | Latin capital letter U with macron                |
     * | U+016B  | ū     | u           | Latin small letter u with macron                  |
     * | U+016C  | Ŭ     | U           | Latin capital letter U with breve                 |
     * | U+016D  | ŭ     | u           | Latin small letter u with breve                   |
     * | U+016E  | Ů     | U           | Latin capital letter U with ring above            |
     * | U+016F  | ů     | u           | Latin small letter u with ring above              |
     * | U+0170  | Ű     | U           | Latin capital letter U with double acute          |
     * | U+0171  | ű     | u           | Latin small letter u with double acute            |
     * | U+0172  | Ų     | U           | Latin capital letter U with ogonek                |
     * | U+0173  | ų     | u           | Latin small letter u with ogonek                  |
     * | U+0174  | Ŵ     | W           | Latin capital letter W with circumflex            |
     * | U+0175  | ŵ     | w           | Latin small letter w with circumflex              |
     * | U+0176  | Ŷ     | Y           | Latin capital letter Y with circumflex            |
     * | U+0177  | ŷ     | y           | Latin small letter y with circumflex              |
     * | U+0178  | Ÿ     | Y           | Latin capital letter Y with diaeresis             |
     * | U+0179  | Ź     | Z           | Latin capital letter Z with acute                 |
     * | U+017A  | ź     | z           | Latin small letter z with acute                   |
     * | U+017B  | Ż     | Z           | Latin capital letter Z with dot above             |
     * | U+017C  | ż     | z           | Latin small letter z with dot above               |
     * | U+017D  | Ž     | Z           | Latin capital letter Z with caron                 |
     * | U+017E  | ž     | z           | Latin small letter z with caron                   |
     * | U+017F  | ſ     | s           | Latin small letter long s                         |
     * | U+01A0  | Ơ     | O           | Latin capital letter O with horn                  |
     * | U+01A1  | ơ     | o           | Latin small letter o with horn                    |
     * | U+01AF  | Ư     | U           | Latin capital letter U with horn                  |
     * | U+01B0  | ư     | u           | Latin small letter u with horn                    |
     * | U+01CD  | Ǎ     | A           | Latin capital letter A with caron                 |
     * | U+01CE  | ǎ     | a           | Latin small letter a with caron                   |
     * | U+01CF  | Ǐ     | I           | Latin capital letter I with caron                 |
     * | U+01D0  | ǐ     | i           | Latin small letter i with caron                   |
     * | U+01D1  | Ǒ     | O           | Latin capital letter O with caron                 |
     * | U+01D2  | ǒ     | o           | Latin small letter o with caron                   |
     * | U+01D3  | Ǔ     | U           | Latin capital letter U with caron                 |
     * | U+01D4  | ǔ     | u           | Latin small letter u with caron                   |
     * | U+01D5  | Ǖ     | U           | Latin capital letter U with diaeresis and macron  |
     * | U+01D6  | ǖ     | u           | Latin small letter u with diaeresis and macron    |
     * | U+01D7  | Ǘ     | U           | Latin capital letter U with diaeresis and acute   |
     * | U+01D8  | ǘ     | u           | Latin small letter u with diaeresis and acute     |
     * | U+01D9  | Ǚ     | U           | Latin capital letter U with diaeresis and caron   |
     * | U+01DA  | ǚ     | u           | Latin small letter u with diaeresis and caron     |
     * | U+01DB  | Ǜ     | U           | Latin capital letter U with diaeresis and grave   |
     * | U+01DC  | ǜ     | u           | Latin small letter u with diaeresis and grave     |
     *
     * Decompositions for Latin Extended-B:
     *
     * |   Code   | Glyph | Replacement |                Description                |
     * | -------- | ----- | ----------- | ----------------------------------------- |
     * | U+0218   | Ș     | S           | Latin capital letter S with comma below   |
     * | U+0219   | ș     | s           | Latin small letter s with comma below     |
     * | U+021A   | Ț     | T           | Latin capital letter T with comma below   |
     * | U+021B   | ț     | t           | Latin small letter t with comma below     |
     *
     * Vowels with diacritic (Chinese, Hanyu Pinyin):
     *
     * |   Code   | Glyph | Replacement |                      Description                      |
     * | -------- | ----- | ----------- | ----------------------------------------------------- |
     * | U+0251   | ɑ     | a           | Latin small letter alpha                              |
     * | U+1EA0   | Ạ     | A           | Latin capital letter A with dot below                 |
     * | U+1EA1   | ạ     | a           | Latin small letter a with dot below                   |
     * | U+1EA2   | Ả     | A           | Latin capital letter A with hook above                |
     * | U+1EA3   | ả     | a           | Latin small letter a with hook above                  |
     * | U+1EA4   | Ấ     | A           | Latin capital letter A with circumflex and acute      |
     * | U+1EA5   | ấ     | a           | Latin small letter a with circumflex and acute        |
     * | U+1EA6   | Ầ     | A           | Latin capital letter A with circumflex and grave      |
     * | U+1EA7   | ầ     | a           | Latin small letter a with circumflex and grave        |
     * | U+1EA8   | Ẩ     | A           | Latin capital letter A with circumflex and hook above |
     * | U+1EA9   | ẩ     | a           | Latin small letter a with circumflex and hook above   |
     * | U+1EAA   | Ẫ     | A           | Latin capital letter A with circumflex and tilde      |
     * | U+1EAB   | ẫ     | a           | Latin small letter a with circumflex and tilde        |
     * | U+1EA6   | Ậ     | A           | Latin capital letter A with circumflex and dot below  |
     * | U+1EAD   | ậ     | a           | Latin small letter a with circumflex and dot below    |
     * | U+1EAE   | Ắ     | A           | Latin capital letter A with breve and acute           |
     * | U+1EAF   | ắ     | a           | Latin small letter a with breve and acute             |
     * | U+1EB0   | Ằ     | A           | Latin capital letter A with breve and grave           |
     * | U+1EB1   | ằ     | a           | Latin small letter a with breve and grave             |
     * | U+1EB2   | Ẳ     | A           | Latin capital letter A with breve and hook above      |
     * | U+1EB3   | ẳ     | a           | Latin small letter a with breve and hook above        |
     * | U+1EB4   | Ẵ     | A           | Latin capital letter A with breve and tilde           |
     * | U+1EB5   | ẵ     | a           | Latin small letter a with breve and tilde             |
     * | U+1EB6   | Ặ     | A           | Latin capital letter A with breve and dot below       |
     * | U+1EB7   | ặ     | a           | Latin small letter a with breve and dot below         |
     * | U+1EB8   | Ẹ     | E           | Latin capital letter E with dot below                 |
     * | U+1EB9   | ẹ     | e           | Latin small letter e with dot below                   |
     * | U+1EBA   | Ẻ     | E           | Latin capital letter E with hook above                |
     * | U+1EBB   | ẻ     | e           | Latin small letter e with hook above                  |
     * | U+1EBC   | Ẽ     | E           | Latin capital letter E with tilde                     |
     * | U+1EBD   | ẽ     | e           | Latin small letter e with tilde                       |
     * | U+1EBE   | Ế     | E           | Latin capital letter E with circumflex and acute      |
     * | U+1EBF   | ế     | e           | Latin small letter e with circumflex and acute        |
     * | U+1EC0   | Ề     | E           | Latin capital letter E with circumflex and grave      |
     * | U+1EC1   | ề     | e           | Latin small letter e with circumflex and grave        |
     * | U+1EC2   | Ể     | E           | Latin capital letter E with circumflex and hook above |
     * | U+1EC3   | ể     | e           | Latin small letter e with circumflex and hook above   |
     * | U+1EC4   | Ễ     | E           | Latin capital letter E with circumflex and tilde      |
     * | U+1EC5   | ễ     | e           | Latin small letter e with circumflex and tilde        |
     * | U+1EC6   | Ệ     | E           | Latin capital letter E with circumflex and dot below  |
     * | U+1EC7   | ệ     | e           | Latin small letter e with circumflex and dot below    |
     * | U+1EC8   | Ỉ     | I           | Latin capital letter I with hook above                |
     * | U+1EC9   | ỉ     | i           | Latin small letter i with hook above                  |
     * | U+1ECA   | Ị     | I           | Latin capital letter I with dot below                 |
     * | U+1ECB   | ị     | i           | Latin small letter i with dot below                   |
     * | U+1ECC   | Ọ     | O           | Latin capital letter O with dot below                 |
     * | U+1ECD   | ọ     | o           | Latin small letter o with dot below                   |
     * | U+1ECE   | Ỏ     | O           | Latin capital letter O with hook above                |
     * | U+1ECF   | ỏ     | o           | Latin small letter o with hook above                  |
     * | U+1ED0   | Ố     | O           | Latin capital letter O with circumflex and acute      |
     * | U+1ED1   | ố     | o           | Latin small letter o with circumflex and acute        |
     * | U+1ED2   | Ồ     | O           | Latin capital letter O with circumflex and grave      |
     * | U+1ED3   | ồ     | o           | Latin small letter o with circumflex and grave        |
     * | U+1ED4   | Ổ     | O           | Latin capital letter O with circumflex and hook above |
     * | U+1ED5   | ổ     | o           | Latin small letter o with circumflex and hook above   |
     * | U+1ED6   | Ỗ     | O           | Latin capital letter O with circumflex and tilde      |
     * | U+1ED7   | ỗ     | o           | Latin small letter o with circumflex and tilde        |
     * | U+1ED8   | Ộ     | O           | Latin capital letter O with circumflex and dot below  |
     * | U+1ED9   | ộ     | o           | Latin small letter o with circumflex and dot below    |
     * | U+1EDA   | Ớ     | O           | Latin capital letter O with horn and acute            |
     * | U+1EDB   | ớ     | o           | Latin small letter o with horn and acute              |
     * | U+1EDC   | Ờ     | O           | Latin capital letter O with horn and grave            |
     * | U+1EDD   | ờ     | o           | Latin small letter o with horn and grave              |
     * | U+1EDE   | Ở     | O           | Latin capital letter O with horn and hook above       |
     * | U+1EDF   | ở     | o           | Latin small letter o with horn and hook above         |
     * | U+1EE0   | Ỡ     | O           | Latin capital letter O with horn and tilde            |
     * | U+1EE1   | ỡ     | o           | Latin small letter o with horn and tilde              |
     * | U+1EE2   | Ợ     | O           | Latin capital letter O with horn and dot below        |
     * | U+1EE3   | ợ     | o           | Latin small letter o with horn and dot below          |
     * | U+1EE4   | Ụ     | U           | Latin capital letter U with dot below                 |
     * | U+1EE5   | ụ     | u           | Latin small letter u with dot below                   |
     * | U+1EE6   | Ủ     | U           | Latin capital letter U with hook above                |
     * | U+1EE7   | ủ     | u           | Latin small letter u with hook above                  |
     * | U+1EE8   | Ứ     | U           | Latin capital letter U with horn and acute            |
     * | U+1EE9   | ứ     | u           | Latin small letter u with horn and acute              |
     * | U+1EEA   | Ừ     | U           | Latin capital letter U with horn and grave            |
     * | U+1EEB   | ừ     | u           | Latin small letter u with horn and grave              |
     * | U+1EEC   | Ử     | U           | Latin capital letter U with horn and hook above       |
     * | U+1EED   | ử     | u           | Latin small letter u with horn and hook above         |
     * | U+1EEE   | Ữ     | U           | Latin capital letter U with horn and tilde            |
     * | U+1EEF   | ữ     | u           | Latin small letter u with horn and tilde              |
     * | U+1EF0   | Ự     | U           | Latin capital letter U with horn and dot below        |
     * | U+1EF1   | ự     | u           | Latin small letter u with horn and dot below          |
     * | U+1EF2   | Ỳ     | Y           | Latin capital letter Y with grave                     |
     * | U+1EF3   | ỳ     | y           | Latin small letter y with grave                       |
     * | U+1EF4   | Ỵ     | Y           | Latin capital letter Y with dot below                 |
     * | U+1EF5   | ỵ     | y           | Latin small letter y with dot below                   |
     * | U+1EF6   | Ỷ     | Y           | Latin capital letter Y with hook above                |
     * | U+1EF7   | ỷ     | y           | Latin small letter y with hook above                  |
     * | U+1EF8   | Ỹ     | Y           | Latin capital letter Y with tilde                     |
     * | U+1EF9   | ỹ     | y           | Latin small letter y with tilde                       |
     *
     * German (`de_DE`), German formal (`de_DE_formal`), German (Switzerland) formal (`de_CH`),
     * and German (Switzerland) informal (`de_CH_informal`) locales:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00C4   | Ä     | Ae          | Latin capital letter A with diaeresis   |
     * | U+00E4   | ä     | ae          | Latin small letter a with diaeresis     |
     * | U+00D6   | Ö     | Oe          | Latin capital letter O with diaeresis   |
     * | U+00F6   | ö     | oe          | Latin small letter o with diaeresis     |
     * | U+00DC   | Ü     | Ue          | Latin capital letter U with diaeresis   |
     * | U+00FC   | ü     | ue          | Latin small letter u with diaeresis     |
     * | U+00DF   | ß     | ss          | Latin small letter sharp s              |
     *
     * Danish (`da_DK`) locale:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00C6   | Æ     | Ae          | Latin capital letter AE                 |
     * | U+00E6   | æ     | ae          | Latin small letter ae                   |
     * | U+00D8   | Ø     | Oe          | Latin capital letter O with stroke      |
     * | U+00F8   | ø     | oe          | Latin small letter o with stroke        |
     * | U+00C5   | Å     | Aa          | Latin capital letter A with ring above  |
     * | U+00E5   | å     | aa          | Latin small letter a with ring above    |
     *
     * Catalan (`ca`) locale:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00B7   | l·l   | ll          | Flown dot (between two Ls)              |
     *
     * Serbian (`sr_RS`) and Bosnian (`bs_BA`) locales:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+0110   | Đ     | DJ          | Latin capital letter D with stroke      |
     * | U+0111   | đ     | dj          | Latin small letter d with stroke        |
     *
     * @since 1.2.1
     * @since 4.6.0 Added locale support for `de_CH`, `de_CH_informal`, and `ca`.
     * @since 4.7.0 Added locale support for `sr_RS`.
     * @since 4.8.0 Added locale support for `bs_BA`.
     *
     * @param string $string Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters.
     */
    public static function remove_accents( $string ) {
        if ( ! preg_match( '/[\x80-\xff]/', $string ) ) {
            return $string;
        }
        if ( self::seems_utf8( $string ) ) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                'ª' => 'a',
                'º' => 'o',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'AE',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ð' => 'D',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'TH',
                'ß' => 's',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'ae',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'd',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'th',
                'ÿ' => 'y',
                'Ø' => 'O',
                // Decompositions for Latin Extended-A
                'Ā' => 'A',
                'ā' => 'a',
                'Ă' => 'A',
                'ă' => 'a',
                'Ą' => 'A',
                'ą' => 'a',
                'Ć' => 'C',
                'ć' => 'c',
                'Ĉ' => 'C',
                'ĉ' => 'c',
                'Ċ' => 'C',
                'ċ' => 'c',
                'Č' => 'C',
                'č' => 'c',
                'Ď' => 'D',
                'ď' => 'd',
                'Đ' => 'D',
                'đ' => 'd',
                'Ē' => 'E',
                'ē' => 'e',
                'Ĕ' => 'E',
                'ĕ' => 'e',
                'Ė' => 'E',
                'ė' => 'e',
                'Ę' => 'E',
                'ę' => 'e',
                'Ě' => 'E',
                'ě' => 'e',
                'Ĝ' => 'G',
                'ĝ' => 'g',
                'Ğ' => 'G',
                'ğ' => 'g',
                'Ġ' => 'G',
                'ġ' => 'g',
                'Ģ' => 'G',
                'ģ' => 'g',
                'Ĥ' => 'H',
                'ĥ' => 'h',
                'Ħ' => 'H',
                'ħ' => 'h',
                'Ĩ' => 'I',
                'ĩ' => 'i',
                'Ī' => 'I',
                'ī' => 'i',
                'Ĭ' => 'I',
                'ĭ' => 'i',
                'Į' => 'I',
                'į' => 'i',
                'İ' => 'I',
                'ı' => 'i',
                'Ĳ' => 'IJ',
                'ĳ' => 'ij',
                'Ĵ' => 'J',
                'ĵ' => 'j',
                'Ķ' => 'K',
                'ķ' => 'k',
                'ĸ' => 'k',
                'Ĺ' => 'L',
                'ĺ' => 'l',
                'Ļ' => 'L',
                'ļ' => 'l',
                'Ľ' => 'L',
                'ľ' => 'l',
                'Ŀ' => 'L',
                'ŀ' => 'l',
                'Ł' => 'L',
                'ł' => 'l',
                'Ń' => 'N',
                'ń' => 'n',
                'Ņ' => 'N',
                'ņ' => 'n',
                'Ň' => 'N',
                'ň' => 'n',
                'ŉ' => 'n',
                'Ŋ' => 'N',
                'ŋ' => 'n',
                'Ō' => 'O',
                'ō' => 'o',
                'Ŏ' => 'O',
                'ŏ' => 'o',
                'Ő' => 'O',
                'ő' => 'o',
                'Œ' => 'OE',
                'œ' => 'oe',
                'Ŕ' => 'R',
                'ŕ' => 'r',
                'Ŗ' => 'R',
                'ŗ' => 'r',
                'Ř' => 'R',
                'ř' => 'r',
                'Ś' => 'S',
                'ś' => 's',
                'Ŝ' => 'S',
                'ŝ' => 's',
                'Ş' => 'S',
                'ş' => 's',
                'Š' => 'S',
                'š' => 's',
                'Ţ' => 'T',
                'ţ' => 't',
                'Ť' => 'T',
                'ť' => 't',
                'Ŧ' => 'T',
                'ŧ' => 't',
                'Ũ' => 'U',
                'ũ' => 'u',
                'Ū' => 'U',
                'ū' => 'u',
                'Ŭ' => 'U',
                'ŭ' => 'u',
                'Ů' => 'U',
                'ů' => 'u',
                'Ű' => 'U',
                'ű' => 'u',
                'Ų' => 'U',
                'ų' => 'u',
                'Ŵ' => 'W',
                'ŵ' => 'w',
                'Ŷ' => 'Y',
                'ŷ' => 'y',
                'Ÿ' => 'Y',
                'Ź' => 'Z',
                'ź' => 'z',
                'Ż' => 'Z',
                'ż' => 'z',
                'Ž' => 'Z',
                'ž' => 'z',
                'ſ' => 's',
                // Decompositions for Latin Extended-B
                'Ș' => 'S',
                'ș' => 's',
                'Ț' => 'T',
                'ț' => 't',
                // Euro Sign
                '€' => 'E',
                // GBP (Pound) Sign
                '£' => '',
                // Vowels with diacritic (Vietnamese)
                // unmarked
                'Ơ' => 'O',
                'ơ' => 'o',
                'Ư' => 'U',
                'ư' => 'u',
                // grave accent
                'Ầ' => 'A',
                'ầ' => 'a',
                'Ằ' => 'A',
                'ằ' => 'a',
                'Ề' => 'E',
                'ề' => 'e',
                'Ồ' => 'O',
                'ồ' => 'o',
                'Ờ' => 'O',
                'ờ' => 'o',
                'Ừ' => 'U',
                'ừ' => 'u',
                'Ỳ' => 'Y',
                'ỳ' => 'y',
                // hook
                'Ả' => 'A',
                'ả' => 'a',
                'Ẩ' => 'A',
                'ẩ' => 'a',
                'Ẳ' => 'A',
                'ẳ' => 'a',
                'Ẻ' => 'E',
                'ẻ' => 'e',
                'Ể' => 'E',
                'ể' => 'e',
                'Ỉ' => 'I',
                'ỉ' => 'i',
                'Ỏ' => 'O',
                'ỏ' => 'o',
                'Ổ' => 'O',
                'ổ' => 'o',
                'Ở' => 'O',
                'ở' => 'o',
                'Ủ' => 'U',
                'ủ' => 'u',
                'Ử' => 'U',
                'ử' => 'u',
                'Ỷ' => 'Y',
                'ỷ' => 'y',
                // tilde
                'Ẫ' => 'A',
                'ẫ' => 'a',
                'Ẵ' => 'A',
                'ẵ' => 'a',
                'Ẽ' => 'E',
                'ẽ' => 'e',
                'Ễ' => 'E',
                'ễ' => 'e',
                'Ỗ' => 'O',
                'ỗ' => 'o',
                'Ỡ' => 'O',
                'ỡ' => 'o',
                'Ữ' => 'U',
                'ữ' => 'u',
                'Ỹ' => 'Y',
                'ỹ' => 'y',
                // acute accent
                'Ấ' => 'A',
                'ấ' => 'a',
                'Ắ' => 'A',
                'ắ' => 'a',
                'Ế' => 'E',
                'ế' => 'e',
                'Ố' => 'O',
                'ố' => 'o',
                'Ớ' => 'O',
                'ớ' => 'o',
                'Ứ' => 'U',
                'ứ' => 'u',
                // dot below
                'Ạ' => 'A',
                'ạ' => 'a',
                'Ậ' => 'A',
                'ậ' => 'a',
                'Ặ' => 'A',
                'ặ' => 'a',
                'Ẹ' => 'E',
                'ẹ' => 'e',
                'Ệ' => 'E',
                'ệ' => 'e',
                'Ị' => 'I',
                'ị' => 'i',
                'Ọ' => 'O',
                'ọ' => 'o',
                'Ộ' => 'O',
                'ộ' => 'o',
                'Ợ' => 'O',
                'ợ' => 'o',
                'Ụ' => 'U',
                'ụ' => 'u',
                'Ự' => 'U',
                'ự' => 'u',
                'Ỵ' => 'Y',
                'ỵ' => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin)
                'ɑ' => 'a',
                // macron
                'Ǖ' => 'U',
                'ǖ' => 'u',
                // acute accent
                'Ǘ' => 'U',
                'ǘ' => 'u',
                // caron
                'Ǎ' => 'A',
                'ǎ' => 'a',
                'Ǐ' => 'I',
                'ǐ' => 'i',
                'Ǒ' => 'O',
                'ǒ' => 'o',
                'Ǔ' => 'U',
                'ǔ' => 'u',
                'Ǚ' => 'U',
                'ǚ' => 'u',
                // grave accent
                'Ǜ' => 'U',
                'ǜ' => 'u',
            );
            // Used for locale-specific rules
            $locale = App::getLocale();
            if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
                $chars['Ä'] = 'Ae';
                $chars['ä'] = 'ae';
                $chars['Ö'] = 'Oe';
                $chars['ö'] = 'oe';
                $chars['Ü'] = 'Ue';
                $chars['ü'] = 'ue';
                $chars['ß'] = 'ss';
            } elseif ( 'da_DK' === $locale ) {
                $chars['Æ'] = 'Ae';
                $chars['æ'] = 'ae';
                $chars['Ø'] = 'Oe';
                $chars['ø'] = 'oe';
                $chars['Å'] = 'Aa';
                $chars['å'] = 'aa';
            } elseif ( 'ca' === $locale ) {
                $chars['l·l'] = 'll';
            } elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
                $chars['Đ'] = 'DJ';
                $chars['đ'] = 'dj';
            }
            $string = strtr( $string, $chars );
        } else {
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                . "\xec\xed\xee\xef\xf1\xf2\xf3"
                . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                . "\xfc\xfd\xff";
            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';
            $string              = strtr( $string, $chars['in'], $chars['out'] );
            $double_chars        = array();
            $double_chars['in']  = array( "\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe" );
            $double_chars['out'] = array( 'OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th' );
            $string              = str_replace( $double_chars['in'], $double_chars['out'], $string );
        }
        return $string;
    }


    /**
     * @param $str
     * @return bool
     */
    public static function seems_utf8( $str ) {
        $length = strlen( $str );
        for ( $i = 0; $i < $length; $i++ ) {
            $c = ord( $str[ $i ] );
            if ( $c < 0x80 ) {
                $n = 0; // 0bbbbbbb
            } elseif ( ( $c & 0xE0 ) == 0xC0 ) {
                $n = 1; // 110bbbbb
            } elseif ( ( $c & 0xF0 ) == 0xE0 ) {
                $n = 2; // 1110bbbb
            } elseif ( ( $c & 0xF8 ) == 0xF0 ) {
                $n = 3; // 11110bbb
            } elseif ( ( $c & 0xFC ) == 0xF8 ) {
                $n = 4; // 111110bb
            } elseif ( ( $c & 0xFE ) == 0xFC ) {
                $n = 5; // 1111110b
            } else {
                return false; // Does not match any model
            }
            for ( $j = 0; $j < $n; $j++ ) { // n bytes matching 10bbbbbb follow ?
                if ( ( ++$i == $length ) || ( ( ord( $str[ $i ] ) & 0xC0 ) != 0x80 ) ) {
                    return false;
                }
            }
        }
        return true;
    }


}