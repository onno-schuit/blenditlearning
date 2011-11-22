<?php
    //
    // This function provides automatic linking to workbook contents of text
    // fields where these fields have autolink enabled.
    //
    // Original code by Williams, Stronk7, Martin D.
    // Modified for workbook module by Vy-Shane SF.

    function workbook_filter($courseid, $text) {
        global $CFG, $DB;

        static $nothingtodo;
        static $contentlist;

        if (!empty($nothingtodo)) {   // We've been here in this page already
            return $text;
        }

        // if we don't have a courseid, we can't run the query, so
        if (empty($courseid)) {
            return $text;
        }

        // Create a list of all the resources to search for. It may be cached already.
        if (empty($contentlist)) {
            // We look for text field contents only, and only if the field has
            // autolink enabled (param1).
            $sql = 'SELECT dc.id AS contentid, ' .
                   'dr.id AS recordid, ' .
                   'dc.content AS content, ' .
                   'd.id AS workbookid ' .
                        'FROM {workbook} d, ' .
                             '{workbook_fields} df, ' .
                             '{workbook_records} dr, ' .
                             '{workbook_content} dc ' .
                            "WHERE (d.course = ? or d.course = '".SITEID."')" .
                            'AND d.id = df.workbookid ' .
                            'AND df.id = dc.fieldid ' .
                            'AND d.id = dr.workbookid ' .
                            'AND dr.id = dc.recordid ' .
                            "AND df.type = 'text' " .
                            "AND " . $DB->sql_compare_text('df.param1', 1) . " = '1'";

            if (!$workbookcontents = $DB->get_records_sql($sql, array($courseid))) {
                return $text;
            }

            $contentlist = array();

            foreach ($workbookcontents as $workbookcontent) {
                $currentcontent = trim($workbookcontent->content);
                $strippedcontent = strip_tags($currentcontent);

                if (!empty($strippedcontent)) {
                    $contentlist[] = new filterobject(
                                            $currentcontent,
                                            '<a class="workbook autolink" title="'.
                                            $strippedcontent.'" href="'.
                                            $CFG->wwwroot.'/mod/workbook/view.php?d='. $workbookcontent->workbookid .
                                            '&amp;rid='. $workbookcontent->recordid .'">',
                                            '</a>', false, true);
                }
            } // End foreach
        }
        return  filter_phrases($text, $contentlist);  // Look for all these links in the text
    }


