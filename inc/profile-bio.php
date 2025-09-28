<?php
/**
 * Build a concise profile summary sentence from ACF user fields.
 */

if (!function_exists('hrphoto_build_profile_summary')) {
    /**
     * Returns a constructed paragraph for the given user, or empty string if nothing to show.
     *
     * @param int $user_id
     * @return string
     */
    function hrphoto_build_profile_summary($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return '';
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }

        // Helper: safe ACF fetch for user fields
        $uf = function ($name) use ($user_id) {
            if (function_exists('get_field')) {
                return get_field($name, 'user_' . $user_id);
            }
            return '';
        };
        // If a select returns [label,value], prefer label; otherwise cast to string
        $val_to_string = function ($v) {
            if (is_array($v)) {
                if (isset($v['label']) && $v['label'] !== '') return (string) $v['label'];
                if (isset($v['value'])) return (string) $v['value'];
                return '';
            }
            return (string) $v;
        };
        // Helper to read a subfield from a group (by group field name)
        $group_sub = function ($group_name, $sub_name) use ($uf, $val_to_string) {
            $g = $uf($group_name);
            if (is_array($g) && isset($g[$sub_name])) {
                return $val_to_string($g[$sub_name]);
            }
            return '';
        };

        // 1) Display name (WordPress)
        $display_name = trim((string) $user->display_name);

        // 2) Experience level
        // Experience may live directly, by key, or under Personal group
        $experience = trim($val_to_string($uf('experience_level')));
        if ($experience === '' && function_exists('get_field')) {
            $experience = trim($val_to_string(get_field('field_68c56689a80ee', 'user_' . $user_id)));
        }
        if ($experience === '') {
            $personal = $uf('personal');
            if (is_array($personal) && isset($personal['experience_level'])) {
                $experience = trim($val_to_string($personal['experience_level']));
            }
        }
        if ($experience === '') {
            // Final fallback: read raw user meta
            $meta_exp = get_user_meta($user_id, 'experience_level', true);
            if (!empty($meta_exp)) { $experience = trim((string) $meta_exp); }
        }
        if ($experience === '') {
            // Namespaced meta: experience group
            $meta_exp2 = get_user_meta($user_id, 'experience_experience_level', true);
            if (!empty($meta_exp2)) { $experience = trim((string) $meta_exp2); }
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $raw_exp_name = function_exists('get_field') ? get_field('experience_level', 'user_' . $user_id) : null;
            $raw_exp_key  = function_exists('get_field') ? get_field('field_68c56689a80ee', 'user_' . $user_id) : null;
            error_log('[hrphoto_profile_summary:exp_raw] name=' . wp_json_encode($raw_exp_name) . ' key=' . wp_json_encode($raw_exp_key));
        }
        // Article a/an based on lowercased experience
        $experience_lower = strtolower($experience);
        $article = 'a';
        if ($experience_lower !== '') {
            $first = substr($experience_lower, 0, 1);
            if (in_array($first, array('a','e','i','o','u'), true)) { $article = 'an'; }
        }

        // 3) Film vs digital phrase
        $shoots_film = strtolower($val_to_string($uf('do_you_shoot_film'))) === 'yes';
        if (!$shoots_film && function_exists('get_field')) {
            $shoots_film = strtolower($val_to_string(get_field('field_68c576044ebe9', 'user_' . $user_id))) === 'yes';
        }
        $film_pct_raw = $shoots_film ? $uf('film') : 0; // numeric 0–100 in 10% steps
        if ($shoots_film && (empty($film_pct_raw) || !is_numeric($film_pct_raw)) && function_exists('get_field')) {
            $film_pct_raw = get_field('field_68c569de41a6d', 'user_' . $user_id);
        }
        $film_pct = is_numeric($film_pct_raw) ? (int) $film_pct_raw : 0;
        $format_phrase = 'digital';
        if ($film_pct >= 30 && $film_pct <= 49) {
            $format_phrase = 'mainly digital and some film';
        } elseif ($film_pct >= 50 && $film_pct <= 69) {
            $format_phrase = 'both digital and film';
        } elseif ($film_pct >= 70) {
            $format_phrase = 'film';
        } // else 0–29 stays 'digital'

        // 4) Location
        $state = trim($val_to_string($uf('statecounty')));
        if ($state === '' && function_exists('get_field')) {
            $state = trim($val_to_string(get_field('field_68c5646f5d94d', 'user_' . $user_id)));
        }
        $country = trim($val_to_string($uf('country_of_residence')));
        if ($country === '' && function_exists('get_field')) {
            $country = trim($val_to_string(get_field('field_68c67b13372e6', 'user_' . $user_id)));
        }
        if ($state === '' || $country === '') {
            $personal = isset($personal) ? $personal : $uf('personal');
            if (is_array($personal)) {
                if ($state === '' && isset($personal['statecounty'])) {
                    $state = trim($val_to_string($personal['statecounty']));
                }
                if ($country === '' && isset($personal['country_of_residence'])) {
                    $country = trim($val_to_string($personal['country_of_residence']));
                }
            }
        }
        if ($state === '') {
            $meta_state = get_user_meta($user_id, 'statecounty', true);
            if (!empty($meta_state)) { $state = trim((string) $meta_state); }
        }
        if ($country === '') {
            $meta_country = get_user_meta($user_id, 'country_of_residence', true);
            if (!empty($meta_country)) { $country = trim((string) $meta_country); }
        }
        // Namespaced location meta fallbacks
        if ($state === '') {
            $ns_state = get_user_meta($user_id, 'location_statecounty', true);
            if (!empty($ns_state)) { $state = trim((string) $ns_state); }
        }
        if ($country === '') {
            $ns_country = get_user_meta($user_id, 'location_country_of_residence', true);
            if (!empty($ns_country)) { $country = trim((string) $ns_country); }
            if ($country === '') {
                $legacy_country = get_user_meta($user_id, 'location_Country', true);
                if (!empty($legacy_country)) { $country = trim((string) $legacy_country); }
            }
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $raw_state_name = function_exists('get_field') ? get_field('statecounty', 'user_' . $user_id) : null;
            $raw_state_key  = function_exists('get_field') ? get_field('field_68c5646f5d94d', 'user_' . $user_id) : null;
            $raw_country_name = function_exists('get_field') ? get_field('country_of_residence', 'user_' . $user_id) : null;
            $raw_country_key  = function_exists('get_field') ? get_field('field_68c67b13372e6', 'user_' . $user_id) : null;
            error_log('[hrphoto_profile_summary:loc_raw] state_name=' . wp_json_encode($raw_state_name) . ' state_key=' . wp_json_encode($raw_state_key) . ' country_name=' . wp_json_encode($raw_country_name) . ' country_key=' . wp_json_encode($raw_country_key));
        }

        // Map country ISO code to label via ACF choices (robust: try name/key, case-insensitive)
        if ($country !== '' && function_exists('get_field_object')) {
            $fo = get_field_object('country_of_residence', 'user_' . $user_id);
            if (!$fo) { $fo = get_field_object('field_68c67b13372e6', 'user_' . $user_id); }
            if ($fo && isset($fo['choices']) && is_array($fo['choices'])) {
                $choices = $fo['choices'];
                $mapped = null;
                // Direct key match
                if (isset($choices[$country])) {
                    $mapped = $choices[$country];
                } else {
                    // Case-insensitive match
                    $needle = strtolower(trim((string) $country));
                    foreach ($choices as $k => $label) {
                        if (strtolower(trim((string) $k)) === $needle) { $mapped = $label; break; }
                    }
                }
                if ($mapped !== null && $mapped !== '') { $country = (string) $mapped; }
            }
        }

        // 5) Primary camera
        // Try direct subfields first, then group("primary_camera")
        $primary_make = trim($val_to_string($uf('primary_camera_make')));
        $primary_model = trim($val_to_string($uf('primary_camera_model')));
        if ($primary_make === '' || $primary_model === '') {
            // Direct group
            if ($primary_make === '') { $primary_make = trim($group_sub('primary_camera', 'primary_camera_make')); }
            if ($primary_model === '') { $primary_model = trim($group_sub('primary_camera', 'primary_camera_model')); }
            // Nested under Photography Gear group
            if ($primary_make === '' || $primary_model === '') {
                $gear = $uf('photography_gear');
                if (is_array($gear) && isset($gear['primary_camera']) && is_array($gear['primary_camera'])) {
                    if ($primary_make === '' && isset($gear['primary_camera']['primary_camera_make'])) {
                        $primary_make = trim($val_to_string($gear['primary_camera']['primary_camera_make']));
                    }
                    if ($primary_model === '' && isset($gear['primary_camera']['primary_camera_model'])) {
                        $primary_model = trim($val_to_string($gear['primary_camera']['primary_camera_model']));
                    }
                }
            }
        }
        $primary_camera = trim(implode(' ', array_filter(array($primary_make, $primary_model))));

        // 6) Favourite go-to lens
        $fav_lens_make = trim($val_to_string($uf('fav_lens_make')));
        $fav_lens_model = trim($val_to_string($uf('fave_lens_model')));
        if ($fav_lens_make === '' || $fav_lens_model === '') {
            // Fallback to group if editor saved as a group array
            $g_candidates = array('favourite_go_to_lens','favorite_go_to_lens','favourite_go_to_lens_group');
            foreach ($g_candidates as $gn) {
                if ($fav_lens_make === '') { $fav_lens_make = trim($group_sub($gn, 'fav_lens_make')); }
                if ($fav_lens_model === '') { $fav_lens_model = trim($group_sub($gn, 'fave_lens_model')); }
            }
            if ($fav_lens_make === '' || $fav_lens_model === '') {
                $gear = isset($gear) ? $gear : $uf('photography_gear');
                if (is_array($gear)) {
                    $paths = array('favourite_go_to_lens','favourite_go_to_lens_group');
                    foreach ($paths as $p) {
                        if (isset($gear[$p]) && is_array($gear[$p])) {
                            if ($fav_lens_make === '' && isset($gear[$p]['fav_lens_make'])) {
                                $fav_lens_make = trim($val_to_string($gear[$p]['fav_lens_make']));
                            }
                            if ($fav_lens_model === '' && isset($gear[$p]['fave_lens_model'])) {
                                $fav_lens_model = trim($val_to_string($gear[$p]['fave_lens_model']));
                            }
                        }
                    }
                }
            }
            // Namespaced user meta fallbacks
            if ($fav_lens_make === '')  { $fav_lens_make  = trim((string) get_user_meta($user_id, 'favourite_lens_fav_lens_make', true)); }
            if ($fav_lens_model === '') { $fav_lens_model = trim((string) get_user_meta($user_id, 'favourite_lens_fave_lens_model', true)); }
        }
        $fav_lens = trim(implode(' ', array_filter(array($fav_lens_make, $fav_lens_model))));

        // 7) Other lenses (up to two)
        $lens1 = trim(implode(' ', array_filter(array($val_to_string($uf('other_lens_make1')), $val_to_string($uf('other_lens_model1'))))));
        $lens2 = trim(implode(' ', array_filter(array($val_to_string($uf('other_lens_make2')), $val_to_string($uf('other_lens_model2'))))));
        if ($lens1 === '' || $lens2 === '') {
            $g = $uf('other_lenses_used_frequently');
            if (is_array($g)) {
                if ($lens1 === '') {
                    $m1 = isset($g['other_lens_make1']) ? $val_to_string($g['other_lens_make1']) : '';
                    $md1 = isset($g['other_lens_model1']) ? $val_to_string($g['other_lens_model1']) : '';
                    $lens1 = trim(implode(' ', array_filter(array($m1, $md1))));
                }
                if ($lens2 === '') {
                    $m2 = isset($g['other_lens_make2']) ? $val_to_string($g['other_lens_make2']) : '';
                    $md2 = isset($g['other_lens_model2']) ? $val_to_string($g['other_lens_model2']) : '';
                    $lens2 = trim(implode(' ', array_filter(array($m2, $md2))));
                }
            }
            if ($lens1 === '' || $lens2 === '') {
                $gear = isset($gear) ? $gear : $uf('photography_gear');
                if (is_array($gear) && isset($gear['other_lenses_used_frequently']) && is_array($gear['other_lenses_used_frequently'])) {
                    $gg = $gear['other_lenses_used_frequently'];
                    if ($lens1 === '') {
                        $m1 = isset($gg['other_lens_make1']) ? $val_to_string($gg['other_lens_make1']) : '';
                        $md1 = isset($gg['other_lens_model1']) ? $val_to_string($gg['other_lens_model1']) : '';
                        $lens1 = trim(implode(' ', array_filter(array($m1, $md1))));
                    }
                    if ($lens2 === '') {
                        $m2 = isset($gg['other_lens_make2']) ? $val_to_string($gg['other_lens_make2']) : '';
                        $md2 = isset($gg['other_lens_model2']) ? $val_to_string($gg['other_lens_model2']) : '';
                        $lens2 = trim(implode(' ', array_filter(array($m2, $md2))));
                    }
                }
            }
            // Namespaced user meta fallbacks for other lenses
            if ($lens1 === '') {
                $m1 = get_user_meta($user_id, 'lenses_other_lenses_other_lens_make1', true);
                $md1 = get_user_meta($user_id, 'lenses_other_lenses_other_lens_model1', true);
                $lens1 = trim(implode(' ', array_filter(array($m1, $md1))));
            }
            if ($lens2 === '') {
                $m2 = get_user_meta($user_id, 'lenses_other_lenses_other_lens_make2', true);
                $md2 = get_user_meta($user_id, 'lenses_other_lenses_other_lens_model2', true);
                $lens2 = trim(implode(' ', array_filter(array($m2, $md2))));
            }
        }
        $other_lenses = array_values(array_filter(array($lens1, $lens2)));

        // 8) Other cameras (prefer first available)
        $other_cam1 = trim(implode(' ', array_filter(array($val_to_string($uf('other_camera_make')), $val_to_string($uf('other_camera_model'))))));
        $other_cam2 = trim(implode(' ', array_filter(array($val_to_string($uf('other_camera_make2')), $val_to_string($uf('other_camera_model2'))))));
        if ($other_cam1 === '' && $other_cam2 === '') {
            $gc = $uf('other_cameras');
            if (is_array($gc)) {
                $m1 = isset($gc['other_camera_make']) ? $val_to_string($gc['other_camera_make']) : '';
                $md1 = isset($gc['other_camera_model']) ? $val_to_string($gc['other_camera_model']) : '';
                $other_cam1 = trim(implode(' ', array_filter(array($m1, $md1))));
                if ($other_cam1 === '') {
                    $m2 = isset($gc['other_camera_make2']) ? $val_to_string($gc['other_camera_make2']) : '';
                    $md2 = isset($gc['other_camera_model2']) ? $val_to_string($gc['other_camera_model2']) : '';
                    $other_cam2 = trim(implode(' ', array_filter(array($m2, $md2))));
                }
            }
            if ($other_cam1 === '' && $other_cam2 === '') {
                $gear = isset($gear) ? $gear : $uf('photography_gear');
                if (is_array($gear) && isset($gear['other_cameras']) && is_array($gear['other_cameras'])) {
                    $gc = $gear['other_cameras'];
                    $m1 = isset($gc['other_camera_make']) ? $val_to_string($gc['other_camera_make']) : '';
                    $md1 = isset($gc['other_camera_model']) ? $val_to_string($gc['other_camera_model']) : '';
                    $other_cam1 = trim(implode(' ', array_filter(array($m1, $md1))));
                    if ($other_cam1 === '') {
                        $m2 = isset($gc['other_camera_make2']) ? $val_to_string($gc['other_camera_make2']) : '';
                        $md2 = isset($gc['other_camera_model2']) ? $val_to_string($gc['other_camera_model2']) : '';
                        $other_cam2 = trim(implode(' ', array_filter(array($m2, $md2))));
                    }
                }
            }
        }
        $other_camera = $other_cam1 !== '' ? $other_cam1 : $other_cam2;

        // 9) Anything else (quoted)
        $anything_else = trim($val_to_string($uf('profile_anything_else')));
        if ($anything_else === '') {
            $personal = isset($personal) ? $personal : $uf('personal');
            if (is_array($personal) && isset($personal['profile_anything_else'])) {
                $anything_else = trim($val_to_string($personal['profile_anything_else']));
            }
        }

        // TEMP DIAGNOSTICS: log key values to help verify ACF sources
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $diag = array(
                'user' => $user_id,
                'experience' => $experience,
                'state' => $state,
                'country' => $country,
                'shoots_film' => $shoots_film ? 'yes' : 'no',
                'film_pct' => $film_pct,
                'primary_camera' => $primary_camera,
                'fav_lens' => $fav_lens,
                'other_lens1' => isset($other_lenses[0]) ? $other_lenses[0] : '',
                'other_lens2' => isset($other_lenses[1]) ? $other_lenses[1] : '',
                'other_camera' => $other_camera,
            );
            error_log('[hrphoto_profile_summary] ' . wp_json_encode($diag));
            error_log('[hrphoto_profile_summary:meta_fallbacks] exp_meta=' . wp_json_encode(get_user_meta($user_id, 'experience_level', true)) . ' state_meta=' . wp_json_encode(get_user_meta($user_id, 'statecounty', true)) . ' country_meta=' . wp_json_encode(get_user_meta($user_id, 'country_of_residence', true)));

            // EXTRA DIAGNOSTICS: dump Personal field definitions and user meta keys
            if (function_exists('acf_get_fields')) {
                $pf = acf_get_fields('group_68c5645993692');
                $names = array();
                if (is_array($pf)) {
                    foreach ($pf as $f) {
                        $names[] = array('name' => isset($f['name']) ? $f['name'] : '', 'key' => isset($f['key']) ? $f['key'] : '');
                    }
                }
                error_log('[hrphoto_profile_summary:personal_fields] ' . wp_json_encode($names));
            }
            $all_meta_keys = array_keys((array) get_user_meta($user_id));
            error_log('[hrphoto_profile_summary:user_meta_keys] ' . wp_json_encode($all_meta_keys));
        }

        $parts = array();

        // Lead sentence mapped by film usage
        $loc_bits = array();
        if ($state !== '') { $loc_bits[] = $state; }
        if ($country !== '') { $loc_bits[] = $country; }
        $loc_text = !empty($loc_bits) ? (' from ' . implode(', ', $loc_bits)) : '';

        $lead_name = $display_name !== '' ? $display_name : '';
        $exp_phrase = $experience_lower !== '' ? ($article . ' ' . $experience_lower) : '';

        // Resolve favourite genre (ACF Taxonomy: Category) and lower-case it
        $genre_value = $uf('fave_genre');
        if ((empty($genre_value) || $genre_value === '') && function_exists('get_field')) {
            $genre_value = get_field('field_68c567a24b1a5', 'user_' . $user_id);
        }
        if ((empty($genre_value) || $genre_value === '')) {
            $personal = isset($personal) ? $personal : $uf('personal');
            if (is_array($personal) && isset($personal['fave_genre']) && !empty($personal['fave_genre'])) {
                $genre_value = $personal['fave_genre'];
            }
        }
        if ((empty($genre_value) || $genre_value === '')) {
            $meta_genre = get_user_meta($user_id, 'fave_genre', true);
            if (!empty($meta_genre)) { $genre_value = $meta_genre; }
        }

        $resolve_term_name = function ($val) {
            if (empty($val) && $val !== 0 && $val !== '0') { return ''; }
            // WP_Term object
            if (is_object($val) && isset($val->name)) { return (string) $val->name; }
            // Numeric ID or numeric string
            if (is_numeric($val)) {
                $term = get_term((int) $val);
                if ($term && !is_wp_error($term) && isset($term->name)) { return (string) $term->name; }
                return '';
            }
            // Associative array with name or term_id
            if (is_array($val)) {
                // If it's a list, take first
                $is_assoc = array_keys($val) !== range(0, count($val) - 1);
                if (!$is_assoc) {
                    foreach ($val as $item) {
                        $n = $resolve_term_name($item);
                        if ($n !== '') { return $n; }
                    }
                    return '';
                }
                if (isset($val['name']) && $val['name'] !== '') { return (string) $val['name']; }
                if (isset($val['term_id']) && is_numeric($val['term_id'])) {
                    $term = get_term((int) $val['term_id']);
                    if ($term && !is_wp_error($term) && isset($term->name)) { return (string) $term->name; }
                }
                if (isset($val['ID']) && is_numeric($val['ID'])) {
                    $term = get_term((int) $val['ID']);
                    if ($term && !is_wp_error($term) && isset($term->name)) { return (string) $term->name; }
                }
                // Some formats may provide only slug; keep as-is if no better
                if (isset($val['slug']) && $val['slug'] !== '') { return (string) $val['slug']; }
            }
            // Fallback plain string
            return is_string($val) ? $val : '';
        };

        $genre_name = $resolve_term_name($genre_value);
        $genre_lower = strtolower(trim(sanitize_text_field((string) $genre_name)));
        $genre_lower = preg_replace('/\s+/', ' ', $genre_lower);
        $genre_phrase = $genre_lower !== '' ? (' ' . $genre_lower) : '';

        if (!$shoots_film || $film_pct <= 10) {
            // 0–10% (or no film): "Jamie is a professional digital [genre] photographer from..."
            if ($exp_phrase !== '') {
                $lead = $lead_name . ' is ' . $exp_phrase . ' digital' . $genre_phrase . ' photographer' . $loc_text . '.';
            } else {
                $lead = $lead_name . ' is a digital' . $genre_phrase . ' photographer' . $loc_text . '.';
            }
        } elseif ($film_pct >= 11 && $film_pct <= 39) {
            // 11–39%: "Jamie is a professional [genre] photographer from..., who mainly shoots digital, but occasionally film."
            $base = $exp_phrase !== '' ? ($lead_name . ' is ' . $exp_phrase . $genre_phrase . ' photographer') : ($lead_name . ' is a' . $genre_phrase . ' photographer');
            $lead = $base . $loc_text . ', who mainly shoots digital, but occasionally film.';
        } elseif ($film_pct >= 40 && $film_pct <= 69) {
            // 40–69%: "Jamie is a professional [genre] photographer from..., who shoots both film and digital formats."
            $base = $exp_phrase !== '' ? ($lead_name . ' is ' . $exp_phrase . $genre_phrase . ' photographer') : ($lead_name . ' is a' . $genre_phrase . ' photographer');
            $lead = $base . $loc_text . ', who shoots both film and digital formats.';
        } elseif ($film_pct >= 70 && $film_pct <= 89) {
            // 70–89%: "Jamie is a professional [genre] photographer from..., specialising mainly in film, but occasionally digital."
            $base = $exp_phrase !== '' ? ($lead_name . ' is ' . $exp_phrase . $genre_phrase . ' photographer') : ($lead_name . ' is a' . $genre_phrase . ' photographer');
            $lead = $base . $loc_text . ', specialising mainly in film, but occasionally digital.';
        } else { // 90–100%
            // 90–100%: "Jamie is a professional film [genre] photographer from..."
            if ($exp_phrase !== '') {
                $lead = $lead_name . ' is ' . $exp_phrase . ' film' . $genre_phrase . ' photographer' . $loc_text . '.';
            } else {
                $lead = $lead_name . ' is a film' . $genre_phrase . ' photographer' . $loc_text . '.';
            }
        }
        $parts[] = $lead;

        // Gear sentence: "Their primary camera is a [primary camera] and they favour the [fav lens]."
        $gear_sentence = '';
        if ($primary_camera !== '' || $fav_lens !== '') {
            if ($primary_camera !== '') {
                $gear_sentence .= 'Their primary camera is the ' . $primary_camera;
            }
            if ($fav_lens !== '') {
                if ($primary_camera !== '') { $gear_sentence .= ' and they '; }
                else { $gear_sentence .= 'They '; }
                $gear_sentence .= 'favour the ' . $fav_lens;
            }
            $gear_sentence .= '.';
            $parts[] = $gear_sentence;
        }

        // Other lenses and other camera sentences (with definite articles and proper conjunction)
        if (!empty($other_lenses)) {
            if (count($other_lenses) === 1) {
                $parts[] = 'Other lenses used include the ' . $other_lenses[0] . '.';
            } else {
                $parts[] = 'Other lenses used include the ' . $other_lenses[0] . ' and the ' . $other_lenses[1] . '.';
            }
        }
        $other_cameras = array_values(array_filter(array($other_cam1, $other_cam2)));
        if (!empty($other_cameras)) {
            if (count($other_cameras) === 1) {
                $parts[] = 'They also use the ' . $other_cameras[0] . '.';
            } else {
                $parts[] = 'They also use the ' . $other_cameras[0] . ' and the ' . $other_cameras[1] . '.';
            }
        }

        // Other genres sentence (Taxonomy: Category - Personal.other_genres)
        $other_genres_value = $uf('other_genres');
        if ((empty($other_genres_value) || $other_genres_value === '') && function_exists('get_field')) {
            $other_genres_value = get_field('field_68c569a241a6c', 'user_' . $user_id);
        }
        if ((empty($other_genres_value) || $other_genres_value === '')) {
            $personal = isset($personal) ? $personal : $uf('personal');
            if (is_array($personal) && isset($personal['other_genres']) && !empty($personal['other_genres'])) {
                $other_genres_value = $personal['other_genres'];
            }
        }

        $other_genre_names = array();
        $uncat_aliases = array('uncategorized','uncategorised');
        $is_uncategorized = function($name, $slug = '') use ($uncat_aliases) {
            $n = strtolower(trim((string) $name));
            $s = strtolower(trim((string) $slug));
            return ($n !== '' && in_array($n, $uncat_aliases, true)) || ($s !== '' && in_array($s, $uncat_aliases, true));
        };
        $collect_other = function ($val) use (&$collect_other, &$other_genre_names, $resolve_term_name, $is_uncategorized) {
            if ($val === null || $val === '' || $val === array()) { return; }
            // WP_Term object
            if (is_object($val) && isset($val->name)) {
                if (!$is_uncategorized($val->name, isset($val->slug) ? $val->slug : '')) {
                    $other_genre_names[] = strtolower(sanitize_text_field((string) $val->name));
                }
                return;
            }
            // Numeric ID
            if (is_numeric($val)) {
                $t = get_term((int) $val);
                if ($t && !is_wp_error($t) && !$is_uncategorized($t->name, isset($t->slug) ? $t->slug : '')) {
                    $other_genre_names[] = strtolower(sanitize_text_field($t->name));
                }
                return;
            }
            // Array (list or assoc)
            if (is_array($val)) {
                $is_assoc = array_keys($val) !== range(0, count($val) - 1);
                if ($is_assoc) {
                    // Prefer resolve by ID if available to get slug
                    if (isset($val['term_id']) || isset($val['ID'])) {
                        $tid = isset($val['term_id']) ? (int) $val['term_id'] : (int) $val['ID'];
                        $t = get_term($tid);
                        if ($t && !is_wp_error($t) && !$is_uncategorized($t->name, isset($t->slug) ? $t->slug : '')) {
                            $other_genre_names[] = strtolower(sanitize_text_field($t->name));
                        }
                        return;
                    }
                    $name = $resolve_term_name($val);
                    $slug = isset($val['slug']) ? (string) $val['slug'] : '';
                    if ($name !== '' && !$is_uncategorized($name, $slug)) {
                        $other_genre_names[] = strtolower(sanitize_text_field($name));
                    }
                } else {
                    foreach ($val as $item) { $collect_other($item); }
                }
                return;
            }
            // String
            if (is_string($val)) {
                if (!$is_uncategorized($val, $val)) {
                    $other_genre_names[] = strtolower(sanitize_text_field($val));
                }
            }
        };
        $collect_other($other_genres_value);

        $other_genre_names = array_values(array_unique(array_filter(array_map('trim', $other_genre_names))));
        if (!empty($other_genre_names)) {
            $count = count($other_genre_names);
            if ($count === 1) {
                $list = $other_genre_names[0];
            } elseif ($count === 2) {
                $list = $other_genre_names[0] . ' and ' . $other_genre_names[1];
            } else {
                $list = implode(', ', array_slice($other_genre_names, 0, -1)) . ' and ' . $other_genre_names[$count - 1];
            }
            $parts[] = 'Other genres ' . $display_name . ' likes capturing include ' . $list . '.';
        }

        // Anything else
        if ($anything_else !== '') {
            $parts[] = 'In their own words: "' . $anything_else . '".';
        }

        $paragraph = trim(implode(' ', $parts));
        return $paragraph;
    }
}


