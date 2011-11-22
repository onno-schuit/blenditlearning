<?php
    // This file adds support to rss feeds generation

    // This function is the main entry point to workbook module
    // rss feeds generation.
    function workbook_rss_get_feed($context, $args) {
        global $CFG, $DB;

        // Check CFG->workbook_enablerssfeeds.
        if (empty($CFG->workbook_enablerssfeeds)) {
            debugging("DISABLED (module configuration)");
            return null;
        }

        $workbookid = clean_param($args[3], PARAM_INT);
        $cm = get_coursemodule_from_instance('workbook', $workbookid, 0, false, MUST_EXIST);
        if ($cm) {
            $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

            //context id from db should match the submitted one
            if ($context->id != $modcontext->id || !has_capability('mod/workbook:viewentry', $modcontext)) {
                return null;
            }
        }

        $workbook = $DB->get_record('workbook', array('id' => $workbookid), '*', MUST_EXIST);
        if (!rss_enabled_for_mod('workbook', $workbook, false, true)) {
            return null;
        }

        $sql = workbook_rss_get_sql($workbook);

        //get the cache file info
        $filename = rss_get_file_name($workbook, $sql);
        $cachedfilepath = rss_get_file_full_name('mod_workbook', $filename);

        //Is the cache out of date?
        $cachedfilelastmodified = 0;
        if (file_exists($cachedfilepath)) {
            $cachedfilelastmodified = filemtime($cachedfilepath);
        }
        //if the cache is more than 60 seconds old and there's new stuff
        $dontrecheckcutoff = time()-60;
        if ( $dontrecheckcutoff > $cachedfilelastmodified && workbook_rss_newstuff($workbook, $cachedfilelastmodified)) {
            require_once($CFG->dirroot . '/mod/workbook/lib.php');

            // Get the first field in the list  (a hack for now until we have a selector)
            if (!$firstfield = $DB->get_record_sql('SELECT id,name FROM {workbook_fields} WHERE workbookid = ? ORDER by id', array($workbook->id), true)) {
                return null;
            }

            if (!$records = $DB->get_records_sql($sql, array(), 0, $workbook->rssarticles)) {
                return null;
            }
            
            $firstrecord = array_shift($records);  // Get the first and put it back
            array_unshift($records, $firstrecord);

            // Now create all the articles
            $items = array();
            foreach ($records as $record) {
                $recordarray = array();
                array_push($recordarray, $record);

                $item = null;

                // guess title or not
                if (!empty($workbook->rsstitletemplate)) {
                    $item->title = workbook_print_template('rsstitletemplate', $recordarray, $workbook, '', 0, true);
                } else { // else we guess
                    $item->title   = strip_tags($DB->get_field('workbook_content', 'content',
                                                      array('fieldid'=>$firstfield->id, 'recordid'=>$record->id)));
                }
                $item->description = workbook_print_template('rsstemplate', $recordarray, $workbook, '', 0, true);
                $item->pubdate = $record->timecreated;
                $item->link = $CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&rid='.$record->id;

                array_push($items, $item);
            }
            $course = $DB->get_record('course', array('id'=>$workbook->course));

            // First all rss feeds common headers.
            $header = rss_standard_header($course->shortname.': '.format_string($workbook->name,true),
                                          $CFG->wwwroot."/mod/workbook/view.php?d=".$workbook->id,
                                          format_string($workbook->intro,true)); //TODO: fix format

            if (!empty($header)) {
                $articles = rss_add_items($items);
            }

            // Now all rss feeds common footers.
            if (!empty($header) && !empty($articles)) {
                $footer = rss_standard_footer();
            }
            // Now, if everything is ok, concatenate it.
            if (!empty($header) && !empty($articles) && !empty($footer)) {
                $rss = $header.$articles.$footer;

                //Save the XML contents to file.
                $status = rss_save_file('mod_workbook', $filename, $rss);
            }
        }

        return $cachedfilepath;
    }

    function workbook_rss_get_sql($workbook, $time=0) {
        //do we only want new posts?
        if ($time) {
            $time = " AND dr.timemodified > '$time'";
        } else {
            $time = '';
        }

        $approved = ($workbook->approval) ? ' AND dr.approved = 1 ' : ' ';

        $sql = "SELECT dr.*, u.firstname, u.lastname
                  FROM {workbook_records} dr, {user} u
                 WHERE dr.workbookid = {$workbook->id} $approved
                       AND dr.userid = u.id $time
              ORDER BY dr.timecreated DESC";

        return $sql;
    }

    /**
     * If there is new stuff in since $time this returns true
     * Otherwise it returns false.
     *
     * @param object $workbook the workbook activity object
     * @param int $time timestamp
     * @return bool
     */
    function workbook_rss_newstuff($workbook, $time) {
        global $DB;

        $sql = workbook_rss_get_sql($workbook, $time);

        $recs = $DB->get_records_sql($sql, null, 0, 1);//limit of 1. If we get even 1 back we have new stuff
        return ($recs && !empty($recs));
    }

