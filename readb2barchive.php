<?php
require_once('templateMyMessages.class.php');

$page = new templateMyMessages(LOGIN, SHOWMSG, REDIRECTSAFE);

$id = optional_param('id', NULL, PARAM_INT);

echo $page->header('B2B Archive: Read Message');
echo mainContent();
echo $page->footer(true);

function mainContent() {
    global $id;

    $one = getOnemsg($id);
    $msgs = getMsgThread($id);

    echo "<h3>B2B Archive: Read Message</h3>\n";
    echo "<article>\n";
    echo "  <div class='entry-content'>\n";
    echo "    <table>\n";
    echo "        <tr>\n";
    echo "          <th>To</th>\n";
    echo "          <td data-label='To'>".$one['to_text']."</td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <th>Date</th>\n";
    echo "          <td data-label='Sent'>".$one['create_date']."</td>\n";
    echo "        </tr>\n";
    echo "        <tr>\n";
    echo "          <th>Subject</th>\n";
    echo "          <td data-label='Subject'>".$one['subj_text']."</td>\n";
    echo "        </tr>\n";
    if (!empty($one["attachment"])) {
        echo "        <tr>\n";
        echo "          <th>Attachment</th>\n";
        $filename   = str_replace("images/blastattachments/", "", $one["attachment"]);
        $path       = "archive/".$filename;
        $url        = "imageviewer.php?img=".$path;
        $link       = "<a href='".$url."' target='_blank'>".$one["attachmentname"]."</a>";
        echo "          <td data-label='Attachment'>".$link."</td>\n";
        echo "        </tr>\n";
    }
    echo "        <tr>\n";
    echo "          <th>Message</th>\n";
    echo "          <td data-label='Message'>".$one['message_text']."</td>\n";
    echo "        </tr>\n";
    echo "    </table>\n";
    echo "    <div>&nbsp;</div>\n";
    echo "    <table class='table'>\n";
    echo "      <thead>\n";
    echo "        <tr>\n";
    echo "          <th colspan='6'>MESSAGE THREAD</th>\n";
    echo "        </tr>";
    echo "        <tr>\n";
    echo "          <th scope='col'>ID</th>\n";
    echo "          <th scope='col'>To</th>\n";
    echo "          <th scope='col'>From</th>\n";
    echo "          <th scope='col'>Subject</th>\n";
    echo "          <th scope='col'>Attachment</th>\n";
    echo "          <th scope='col'>Date</th>\n";
    echo "        </tr>\n";
    echo "     	</thead>\n";
    echo "     	<tbody>\n";
    if (!empty($msgs)) {
        $x = 0;
        $odd = "";
        $even = "style='background-color:#ccc;'";
        foreach ($msgs as $m) {
            $x++;
            $style = ($x % 2) ? $odd : $even;
            if ($id == $m['id']) {
                echo "        <tr style='background-color: #8BEFFF; padding-top:10px; padding-bottom:10px;'>\n";
                echo "          <td>".$m['id']."</td>\n";
                echo "          <td colspan='5'>This Message</td>\n";
                echo "        </tr>\n";
            } else {
                echo "        <tr ".$style.">\n";
                echo "          <td data-label='Ref #'>".$m['id']."</td>\n";
                echo "          <td data-label='To'>".$m['to_text']."</td>\n";
                echo "          <td data-label='From'>".$m['from_text']."</td>\n";
                $url = "readb2barchive.php?id=".$m['id'];
                $link = "<a href='".$url."'>".stripslashes($m["subj_text"])."</a>";
                echo "            <td data-label='Subject'>".$link."</td>\n";
                if (!empty($m["attachment"])) {
                    $filename   = str_replace("images/blastattachments/", "", $m["attachment"]);
                    $path       = "archive/".$filename;
                    $url        = "imageviewer.php?img=".$path;
                    $link       = "<a href='".$url."' target='_blank'>".$m["attachmentname"]."</a>";
                    echo "          <td data-label='Attachment'>".$link."</td>\n";
                } else {
                    echo "          <td data-label='Attachment'>&nbsp;</td>\n";
                }
                echo "          <td data-label='Sent'>".$m['create_date']."</td>\n";
                echo "        </tr>\n";
                if ($id <> $m['id']) {
                    echo "        <tr ".$style.">\n";
                    echo "          <td data-label='Message' colspan='6'>".$m['message_text']."</td>\n";
                    echo "        </tr>\n";
                }
            }
        }
    }
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
    echo "</article>\n";


}

function getOnemsg($id) {
    global $page;

    $sql = "
        SELECT m.id, m.parent_id, m.to_text, m.from_text, m.subj_text, m.create_date,
               m.attachment, m.attachmentname, mt.message_text
          FROM b2b_archive.messages         m
          JOIN b2b_archive.messages_text    mt  ON  mt.id = m.id
         WHERE m.id = ".$id."
           AND m.message_type IN ('EMAIL', 'BULKMAIL', 'OFFER')
           AND (m.from_id = ".$page->user->userId."
                OR m.to_id = ".$page->user->userId.")
    ";

    $data = $page->db->sql_query_params($sql);
    $result = reset($data);

    return $result;
}

function GetMsgHistory($id) {
    global $page;

    $sql = "
        WITH RECURSIVE rows AS (
            SELECT id, parent_id, from_text, to_text, subj_text, create_date, 1 as level
             FROM b2b_archive.messages
            WHERE id = ".$id."
            UNION ALL
            SELECT t.id, t.parent_id,  t.from_text, t.to_text, t.subj_text, t.create_date, r.level + 1
             FROM b2b_archive.messages t
             JOIN rows r ON r.id = t.parent_id
            )
     SELECT *
      FROM rows
     ORDER BY create_date DESC
    ";

    $messagesAfter = $page->db->sql_query_params($sql);
echo "<pre>";
print_r($messagesAfter);
echo "</pre>";
    $sql = "
        WITH RECURSIVE rows AS (
            SELECT id, parent_id, from_text, to_text, subj_text, create_date, 1 as level
             FROM b2b_archive.messages
            WHERE id = ".$id."
            UNION ALL
            SELECT t.id, t.parent_id,  t.from_text, t.to_text, t.subj_text, t.create_date, r.level - 1
             FROM b2b_archive.messages t
             JOIN rows r ON r.parent_id = t.id
            )
     SELECT *
      FROM rows
     ORDER BY create_date DESC
    ";
echo "<pre>";
print_r($messagesAfter);
echo "</pre>";

    $messagesBefore = $page->db->sql_query_params($sql);

    $messages = array_unique(array_merge($messagesAfter,$messagesBefore), SORT_REGULAR);
    if (!empty($messagesAfter) && !empty($messagesBefore)) {
        return($messages);
    } elseif (!empty($messagesAfter)) {
        return $messagesAfter;
    } else {
        return $messagesBefore;
    }

}

function getMsgThread($id) {
    global $page;

    $ids = array($id=>$id);
    $newids = $id;
    $trips = 0;
    do {
        $rs = GetHistoryParentIds($newids);
        $newids = "";
        if (!empty($rs)) {
            $cnt = count($rs);
            foreach($rs as $r) {
                if (!empty($r["parent_id"])) {
                    $newids .= (empty($newids)) ? $r["parent_id"] : ",".$r["parent_id"];
                    $ids[$r["parent_id"]] = $r["parent_id"];
                } else {
                    $cnt = 0;
                }
            }
        } else {
            $cnt = 0;
        }
    } while ($cnt > 0);

    $thread = implode(",", $ids);
    $rs = GetHistoryParentIds($thread);
    if (!empty($rs)) {
        foreach($rs as $r) {
            if (!empty($r["parent_id"])) {
                $ids[$r["parent_id"]] = $r["parent_id"];
            }
        }
    }
    $rs = GetHistoryIds($thread);
    if (!empty($rs)) {
        foreach($rs as $r) {
            $ids[$r["id"]] = $r["id"];
        }
    }

    $newids = $id;
    do {
        $rs = GetHistoryIds($newids);
        $newids = "";
        if (!empty($rs)) {
            $cnt = count($rs);
            foreach($rs as $r) {
                $newids .= (empty($newids)) ? $r["id"] : ",".$r["id"];
                $ids[$r["id"]] = $r["id"];
            }
        } else {
            $cnt = 0;
        }
    } while ($cnt > 0);

    $thread = implode(",", $ids);
    $rs = GetHistoryIds($thread);
    if (!empty($rs)) {
        foreach($rs as $r) {
            $ids[$r["id"]] = $r["id"];
        }
    }

    $thread = implode(",", $ids);
    $rs = GetHistoryParentIds($thread);
    if (!empty($rs)) {
        foreach($rs as $r) {
            if (!empty($r["parent_id"])) {
                $ids[$r["parent_id"]] = $r["parent_id"];
            }
        }
    }

    $thread = implode(",", $ids);
    $sql = "
        SELECT m.id, m.parent_id, m.to_text, m.from_text, m.subj_text, m.create_date,
               m.attachment, m.attachmentname, mt.message_text
          FROM b2b_archive.messages         m
          JOIN b2b_archive.messages_text    mt  ON  mt.id = m.id
         WHERE m.id in (".$thread.")
           AND m.message_type IN ('EMAIL', 'BULKMAIL', 'OFFER')
        UNION
        SELECT m.id, m.parent_id, m.to_text, m.from_text, m.subj_text, m.create_date,
               m.attachment, m.attachmentname, mt.message_text
          FROM b2b_archive.messages         m
          JOIN b2b_archive.messages_text    mt  ON  mt.id = m.id
         WHERE m.parent_id in (".$thread.")
           AND m.message_type IN ('EMAIL', 'BULKMAIL', 'OFFER')
        ORDER BY id DESC
    ";

//  echo "<pre>".$sql."</pre>";
    $data = $page->db->sql_query_params($sql);

    return $data;

}

function GetHistoryParentIds($ids) {
    global $page;

    $sql = "
        SELECT parent_id
          FROM b2b_archive.messages
         WHERE id in (".$ids.")
    ";

    return($page->db->sql_query_params($sql));
}

function GetHistoryIds($ids) {
    global $page;

    $sql = "
        SELECT id
          FROM b2b_archive.messages
         WHERE parent_id in (".$ids.")
    ";

    return($page->db->sql_query_params($sql));
}

?>