<?php
require_once("twilio_sms.php");

DEFINE("UNREADSTATUS",      "UNREAD");
DEFINE("READSTATUS",        "READ");

DEFINE("BULKMAIL",          "BULKMAIL");
DEFINE("BULKMAILSUBSCRIBE", "BULKMAILSUBSCRIBE");
DEFINE("EMAIL",             "EMAIL");
DEFINE("COMPLAINT",         "COMPLAINT");
DEFINE("BOARD",             "BOARD");
DEFINE("OFFERCHAT",         "OFFERCHAT");
DEFINE("OFFER",             "OFFER");
DEFINE("OFFERDOC",          "OFFERDOC");
DEFINE("BLASTTYPE",         "Blast");

DEFINE("INBOX",             "INBOX");
DEFINE("OUTBOX",            "OUTBOX");
DEFINE("BULK",              "BULK");
DEFINE("ACCEPTED",          "OFFER ACCEPTED");
DEFINE("PENDING",           "OFFER PENDING");
DEFINE("EXPIRED",           "OFFER EXPIERED/DECLINED");
DEFINE("ARCHIVE",           "ARCHIVE");
DEFINE("ACCEPTEDSTATUS",    "ACCEPTED");
DEFINE("PENDINGSTATUS",     "PENDING");

DEFINE("ADMINUSERNAME",     "ADMIN");
DEFINE("ADMINUSERID",       321);

DEFINE("HELPDESKUSERNAME",     "B2B-HELP");
DEFINE("HELPDESKUSERID",       50764);

DEFINE("SYSTEMNAME",        "System");
DEFINE("SYSTEMUSERID",      1);
DEFINE("BLASTCATID",        1603);
DEFINE("BLASTPREFID",       1);

DEFINE("EMAILNOTIFICATIONTYPE",         "EMAIL");
DEFINE("SMSNOTIFICATIONTYPE",           "SMS");

class internalMessage {
    private $db;
    private $utility;
    private $user;
    private $messages;
    private $page;
    private $attachmentDir;

    public function __construct() {
        global $CFG, $DB, $USER, $UTILITY;

        $this->db       = $DB;
        $this->user     = $USER;
        $this->utility  = $UTILITY;

        $this->attachmentDir = $CFG->attachments;
    }

    public function insertMessage($page, $toId, $toText, $subjectText, $messageText, $messageType,
                                  $threadId = NULL, $parentId = NULL, $offerid = NULL, $replyrequired = 0,
                                  $attachment = NULL, $attachmentName = NULL, $fileToUpload = NULL) {
        return $this->InsertMessageFrom($page, $this->user->userId, $this->user->username, $toId, $toText,
                                    $subjectText, $messageText, $messageType,
                                    $threadId, $parentId, $offerid, $replyrequired,
                                    $attachment, $attachmentName, $fileToUpload);
    }

    public function insertMessageFrom($page, $fromId, $fromText, $toId, $toText, $subjectText, $messageText, $messageType,
                                  $threadId = NULL, $parentId = NULL, $offerid = NULL, $replyrequired = 0,
                                  $attachment = NULL, $attachmentName = NULL, $fileToUpload = NULL) {
        $localAttachmentName = NULL;

        if (!empty($toId) && !empty($toText) && !empty($subjectText) && !empty($messageText)) {
            $parentId   = (empty($parentId)) ? 0 : $parentId;
            $threadId   = (empty($threadId)) ? intval($this->utility->nextval("messaging_threadid_seq")) : $threadId;
            $messageId  = $this->utility->nextval("messaging_messageid_seq");

            $proceed = true;
            if (isset($fileToUpload["name"]) && !empty($fileToUpload["name"])) {
                $newName = $messageId;
                $localAttachmentName = $this->attachFile($fileToUpload, $newName, $this->attachmentDir, $page);
                if (empty($localAttachmentName)) {
                    $proceed = false;
                    $messageId = 0;
                }
            }

            $sql = "
                INSERT INTO messaging(messageid, threadId, parentId, fromId, fromText, toId, toText, subjectText,
                                      messageText, messageType, offerid, replyrequired, attachment, attachmentName, status, createdBy)
                               VALUES(:messageid, :threadId, :parentId, :fromId, :fromText, :toId, :toText, :subjectText,
                                      :messageText, :messageType, :offerid, :replyrequired, :attachment, :attachmentName, :status, :createdBy)
            ";

            $params = array();
            $params['messageid']        = $messageId;
            $params['threadId']         = $threadId;
            $params['parentId']         = $parentId;
            $params['fromId']           = $fromId;
            $params['fromText']         = $fromText;
            $params['toId']             = $toId;
            $params['toText']           = strtoupper($toText);
            $params['subjectText']      = $subjectText;
            $params['messageText']      = $messageText;
            $params['messageType']      = $messageType;
            $params['offerid']          = (empty($offerid)) ? NULL : $offerid;
            $params['replyrequired']    = (empty($replyrequired)) ? 0 : 1;
            $params['attachment']       = $localAttachmentName;
            $params['attachmentName']   = $attachmentName;
            $params['status']           = UNREADSTATUS;
            $params['createdBy']        = $this->user->username;

//echo "<pre>".$sql."<br>";
//print_r($params);
//echo "</pre>";
            if ($proceed) {
                try {
                    $this->db->sql_execute_params($sql, $params);
                    if ($messageType <> BULKMAIL) {
                        $externalBody = "<p>You have received a new message from ".$this->user->username." on <a href='https://www.dealernetx.com'>DealernetX.com</a></p>";
//                        $this->sendExternalEmail($toId, $subjectText, $externalBody);
                    }
                } catch (Exception $e) {
                    $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to send message.]");
                    $messageId = 0;
                }
            }
        } else {
            $page->messages->addErrorMsg("ERROR: Unable to send message. [Missing required fields]");
            $messageId = 0;
        }

        return $messageId;
    }

    public function insertSystemMessage($page, $toId, $toText, $subjectText, $messageText, $messageType,
                                  $threadId = NULL, $parentId = NULL, $offerid = NULL, $replyrequired = 0,
                                  $attachment = NULL, $attachmentName = NULL, $fileToUpload = NULL) {
        $localAttachmentName = NULL;

        if (!empty($toId) && !empty($toText) && !empty($subjectText) && !empty($messageText)) {
            $parentId   = (empty($parentId)) ? 0 : $parentId;
            $threadId   = (empty($threadId)) ? intval($this->utility->nextval("messaging_threadid_seq")) : $threadId;
            $messageId  = $this->utility->nextval("messaging_messageid_seq");

            $proceed = true;
            if (isset($fileToUpload["name"]) && !empty($fileToUpload["name"])) {
                $newName = $messageId;
                $localAttachmentName = $this->attachFile($fileToUpload, $newName, $this->attachmentDir, $page);
                if (empty($localAttachmentName)) {
                    $proceed = false;
                    $messageId = 0;
                    $page->messages->addErrorMsg("ERROR: Unable to send message. [Invalid attachment]");
                }
            }

            if ($proceed) {
                $sql = "
                    INSERT INTO messaging(messageid, threadId, parentId, fromId, fromText, toId, toText, subjectText,
                                          messageText, messageType, offerid, replyrequired, attachment, attachmentName, status, createdBy)
                                   VALUES(:messageid, :threadId, :parentId, :fromId, :fromText, :toId, :toText, :subjectText,
                                          :messageText, :messageType, :offerid, :replyrequired, :attachment, :attachmentName, :status, :createdBy)
                ";

                $params = array();
                $params['messageid']        = $messageId;
                $params['threadId']         = $threadId;
                $params['parentId']         = $parentId;
                $params['fromId']           = SYSTEMUSERID;
                $params['fromText']         = SYSTEMNAME;
                $params['toId']             = $toId;
                $params['toText']           = strtoupper($toText);
                $params['subjectText']      = $subjectText;
                $params['messageText']      = $messageText;
                $params['messageType']      = $messageType;
                $params['offerid']          = (empty($offerid)) ? NULL : $offerid;
                $params['replyrequired']    = (empty($replyrequired)) ? 0 : 1;
                $params['attachment']       = $localAttachmentName;
                $params['attachmentName']   = $attachmentName;
                $params['status']           = UNREADSTATUS;
                $params['createdBy']        = SYSTEMNAME;
                try {
                    if ($this->db->sql_execute_params($sql, $params)) {
                        if ($messageType <> BULKMAIL) {
                            $externalBody = "<p>You have received a new message on <a href='https://www.dealernetx.com'>DealernetX.com</a></p>";
//                            $this->sendExternalEmail($toId, $subjectText, $externalBody);
                        }
                    } else {
                        $page->messages->addErrorMsg("ERROR: [Unable to send message.]");
                        $messageId = 0;
                    }
                } catch (Exception $e) {
                    $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to send message.]");
                    $messageId = 0;
                }
            }
        } else {
            $page->messages->addErrorMsg("ERROR: Unable to send message. [Missing required fields]");
            $messageId = 0;
        }

        return $messageId;
    }

    public function attachFile($fileToUpload, $newName, $target_dir, &$page) {
        global $CFG;

        $array  = explode('.', $fileToUpload["name"]);
        $ext    = end($array);
        $ext    = strtolower($ext);

        $imageName = NULL;
        $target_name = $newName.".".$ext;
        $target_file = $target_dir.$target_name;

        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png"  && $imageFileType != "jpeg" && $imageFileType != "gif" &&
            $imageFileType != "doc" && $imageFileType != "docx" && $imageFileType != "pdf"  && $imageFileType != "txt") {
            $page->messages->addErrorMsg("ERROR: only jpg, jpeg, png, gif, doc, docx, pdf, txt files are permitted.");
            $uploadOk = 0;
        }

        if (isset($fileToUpload)) {
            if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                $check = getimagesize($fileToUpload["tmp_name"]);
                if ($check !== false) {
                } else {
                    $page->messages->addErrorMsg("ERROR: File is not an image.");
                    $uploadOk = 0;
                }
            }
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $page->messages->addErrorMsg("ERROR: File exists; please select another name.");
            $uploadOk = 0;
        }

        // Check file size
        if ($fileToUpload["size"] > $CFG->ATTACH_MAX_UPLOAD) {
            $page->messages->addErrorMsg("ERROR: File is too large.");
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            $newName = NULL;
        } else {
            if (move_uploaded_file($fileToUpload["tmp_name"], $target_file)) {
                chmod($target_file, 0666);
                $filename = $fileToUpload["name"];
                $imageName = $target_name;

                $page->messages->addSuccessMsg("The file ".$filename. " has been uploaded.");
            } else {
                $page->messages->addErrorMsg("Sorry, there was an error uploading your file.");
            }
        }

        return $imageName;
    }

    public function getMessagesNoBody($includestatus = NULL, $pagenum = 1, $perpage = NULL, $searchstring=NULL, $fromdate=NULL, $todate=NULL) {
        global $CFG, $totalRows, $USER;

        $perpage = (isset($perpage)) ? $perpage : $CFG->PERPAGE;

        $include = (empty($includestatus)) ? "'".UNREADSTATUS."'" : $includestatus;
        $includewhere = "
               AND msg.status IN (".$include.")
        ";
        $bypasssort = (strpos($includestatus, "'".READSTATUS."'") !== false) ? 1 : 0;

        $where = "";
        if (!empty($searchstring)) {
            $bypasssort = 1;
            $searchstring = strtolower(trim($searchstring));
            $includewhere = "";
            $where .= "
               AND (   strpos(lower(u.username), '".$searchstring."') > 0
                    OR strpos(lower(msg.fromtext), '".$searchstring."') > 0
                    OR strpos(lower(msg.subjecttext), '".$searchstring."') > 0
                    OR strpos(lower(msg.messagetext), '".$searchstring."') > 0
                    OR strpos(lower(msg.messageid::varchar), '".$searchstring."') > 0
                   )
            ";
        }
        if (!empty($fromdate) && (!empty($todate))) {
            $includewhere = "";
            $where .= "
               AND msg.createdate BETWEEN ".$fromdate." AND enddatetime(".$todate.")
            ";
        }

        $selectSql = "
            SELECT msg.messageid, msg.threadid, msg.parentid,
                   msg.fromid, u.username as fromtext, msg.toid, msg.totext,
                   msg.messagetype, msg.subjecttext, msg.attachment, msg.attachmentname,
                   msg.status, msg.createdate, msg.modifydate, msg.offerid,
                   ply.createdate as datereplied,
                   case when msg.replyrequired = 1 AND ply.createdate IS NULL then 1
                        else 0 end as replyneeded,
                   0 as offerexpiration,
                   0 as completedon,
                   CASE WHEN ar.userid IS NOT NULL THEN 1
                        ELSE 0 END      as iselite,
                   ui.listinglogo,
                   CASE WHEN ar2.userid IS NOT NULL THEN 1
                        WHEN msg.fromid = ".SYSTEMUSERID." THEN 1
                        ELSE 0 END      as staffsystem,
                   CASE WHEN ".$bypasssort." = 0 AND ar2.userid IS NOT NULL THEN 1
                        WHEN ".$bypasssort." = 0 AND msg.fromid = ".SYSTEMUSERID." THEN 1
                        ELSE 99 END     as sortby";
        $sql = "
              FROM messaging            msg
              JOIN users                u   ON  u.userid        = msg.fromid
              JOIN userinfo             ui  ON  ui.userId       = u.userid
              LEFT JOIN messaging       ply ON  ply.parentid    = msg.messageid
              LEFT JOIN assignedrights  ar  ON  ar.userid       = ui.userid
                                            AND ar.userrightid  = ".USERRIGHT_ELITE."
              LEFT JOIN assignedrights  ar2 ON  ar2.userid       = msg.fromid
                                            AND ar2.userrightid  = ".USERRIGHT_STAFF."
             WHERE msg.messagetype IN ('".EMAIL."', '".BULKMAIL."', '".OFFERCHAT."', '".OFFERDOC."', '".COMPLAINT."')
               AND msg.replyrequired    = 0
               AND msg.toid             = ".$this->user->userId;
        $sql .= $includewhere.$where;

        $totalRows = $this->db->get_field_query("SELECT count(1) as cnt ".$sql);

        $unionSql = "
            UNION
        ";
        $selectSql2 = "
            SELECT msg.messageid, msg.threadid, msg.parentid,
                   msg.fromid, u.username as fromtext, msg.toid, msg.totext,
                   msg.messagetype, msg.subjecttext, msg.attachment, msg.attachmentname,
                   msg.status, msg.createdate, msg.modifydate, msg.offerid,
                   ply.createdate as datereplied,
                   case when msg.replyrequired = 1 AND ply.createdate IS NULL then 1
                        else 0 end as replyneeded,
                   0 as offerexpiration,
                   0 as completedon,
                   CASE WHEN ar.userid IS NOT NULL THEN 1
                        ELSE 0 END      as iselite,
                   ui.listinglogo,
                   CASE WHEN ar2.userid IS NOT NULL THEN 1
                        WHEN msg.fromid = ".SYSTEMUSERID." THEN 1
                        ELSE 0 END      as staffsystem,
                   1                    as sortby";
        $sql2 = "
              FROM messaging            msg
              JOIN users                u   ON  u.userid        = msg.fromid
              JOIN userinfo             ui  ON  ui.userId       = u.userid
              LEFT JOIN messaging       ply ON  ply.parentid    = msg.messageid
              LEFT JOIN assignedrights  ar  ON  ar.userid       = ui.userid
                                            AND ar.userrightid  = ".USERRIGHT_ELITE."
              LEFT JOIN assignedrights  ar2 ON  ar2.userid       = ui.userid
                                            AND ar2.userrightid  = ".USERRIGHT_STAFF."
             WHERE msg.messagetype IN ('".EMAIL."', '".BULKMAIL."', '".OFFERCHAT."', '".OFFERDOC."', '".COMPLAINT."')
               AND msg.toid             = ".$this->user->userId."
               AND msg.replyrequired    = 1
               AND ply.messageid IS NULL
        ";
        $totalRows += $this->db->get_field_query("SELECT count(1) as cnt ".$sql2);

            $selectSql3 = "
                SELECT l.listingid as messageid, null as threadid,  null as parentid,
                       l.userid as fromid, u.username as fromtext, ".$this->user->userId." as toid, '".$this->user->username."' as totext,
                       c.categoryname as messagetype, '".BLASTTYPE.": ' || l.title as subjecttext, null asattachment, null as attachmentname,
                       '".UNREADSTATUS."' as status, l.createdate, l.modifydate, null as offerid,
                       null as datereplied,
                       0 as replyneeded,
                       0 as offerexpiration,
                       0 as completedon,
                       CASE WHEN ar.userid IS NOT NULL THEN 1
                            ELSE 0 END      as iselite,
                       ui.listinglogo,
                       0                    as staffsystem,
                       99                   as sortby";
            $sql3 = "
                  FROM listings             l
                  JOIN categories           c   ON  c.categoryid    = l.categoryid
                  JOIN users                u   ON  u.userid        = l.userid
                  JOIN userinfo             ui  ON  ui.userId       = u.userid
                  LEFT JOIN assignedrights  ar  ON  ar.userid       = ui.userid
                                                AND ar.userrightid  = ".USERRIGHT_ELITE."
                  LEFT JOIN assignedrights  ar2 ON  ar2.userid      = ui.userid
                                                AND ar2.userrightid = ".USERRIGHT_STAFF."
                 WHERE l.categoryid = ".BLASTCATID."
                   AND l.status     = 'OPEN'
            ";
            if ($USER->hasUserPrefs(BLASTPREFID)) {
                $sql3 .= "       AND inttodatetime(l.modifydate)::TIMESTAMP + interval '7' day > now()
            ";
            } else {
                $sql3 .= "       AND inttodatetime(l.modifydate)::TIMESTAMP + interval '24' hour > now()
            ";
            }
            $totalRows += $this->db->get_field_query("SELECT count(1) as cnt ".$sql3);

            $everythingSql = $selectSql.$sql.$unionSql.$selectSql2.$sql2.$unionSql.$selectSql3.$sql3;

        $everythingSql .= "
             ORDER BY sortby, createdate DESC, messageid DESC
            OFFSET ".($pagenum-1)*$perpage."
             LIMIT ".$perpage;

//      echo "<pre>".$everythingSql."</pre>";
        $data = $this->db->sql_query_params($everythingSql);

        return $data;
    }

    public function getMessagesNoBody_Sent($includestatus = NULL, $pagenum = 1, $perpage = NULL, $searchstring=NULL, $fromdate=NULL, $todate=NULL) {
        global $CFG, $totalRows;

        $perpage = (isset($perpage)) ? $perpage : $CFG->PERPAGE;

        $include = (empty($includestatus)) ? "'".UNREADSTATUS."'" : $includestatus;
        $includewhere = "
               AND msg.status IN (".$include.")";
        $where = "";
        if (!empty($searchstring)) {
            $searchstring = strtolower(trim($searchstring));
            $includewhere = "";
            $where .= "
               AND (   strpos(lower(msg.totext), '".$searchstring."') > 0
                    OR strpos(lower(msg.subjecttext), '".$searchstring."') > 0
                    OR strpos(lower(msg.messagetext), '".$searchstring."') > 0
                    OR strpos(lower(msg.messageid::varchar), '".$searchstring."') > 0
                   )";
        }
        if (!empty($fromdate) && (!empty($todate))) {
            $includewhere = "";
            $where .= "
               AND msg.createdate BETWEEN ".$fromdate." AND enddatetime(".$todate.")";
        }

        $selectSql = "
            SELECT msg.messageid, msg.threadid, msg.parentid,
                   msg.fromid, msg.fromtext, msg.toid, msg.totext,
                   msg.messagetype, msg.subjecttext, msg.attachment, msg.attachmentname,
                   msg.status, msg.createdate, msg.modifydate, msg.offerid,
                   ply.createdate as datereplied";
        $sql = "
              FROM messaging        msg
              LEFT JOIN messaging   ply ON  ply.parentid    = msg.messageid
             WHERE msg.messagetype IN ('EMAIL', 'BULKMAIL')
               AND msg.offerid IS NULL
               AND msg.status IN (".$include.")
               AND msg.fromid           = ".$this->user->userId;
        $sql .= $includewhere.$where;

        $totalRows = $this->db->get_field_query("SELECT count(1) as cnt ".$sql);

        $sql .= "
             ORDER BY createdate DESC, msg.totext DESC
            OFFSET ".($pagenum-1)*$perpage."
             LIMIT ".$perpage;

        $data = $this->db->sql_query_params($selectSql.$sql);

        return $data;
    }

    public function getMailThread($threadId, $messageId) {

        $sql = "
            SELECT msg.messageId, msg.threadId, msg.parentId, msg.fromId, msg.fromText, msg.toId,
                   msg.toText, msg.messageType, msg.subjectText, msg.messageText,msg.attachment,
                   msg.attachmentName,msg.status, msg.createdate, msg.modifydate
              FROM messaging msg
             WHERE msg.threadid = ".$threadId."
               AND msg.messageid <> ".$messageId."
               AND (msg.toid = ".$this->user->userId."
                    OR msg.fromid = ".$this->user->userId.")
             ORDER BY createdate DESC
        ";

//        echo "<pre>".$sql."</pre>";
        $data = $this->db->sql_query_params($sql);

        return $data;
    }

    public function getMessage($messageId) {
        $sql = "
            SELECT msg.messageid, msg.threadid, msg.parentid, msg.fromid, u.username as fromtext, msg.toid,
                   msg.totext, msg.messagetype, msg.subjecttext, msg.messagetext,msg.attachment, msg.attachmentname,
                   msg.status, msg.createdate, msg.modifydate, msg.offerid, msg.replyrequired,
                   a.companyname, a.phone, a.fax, a.email, a.firstname, a.lastname,
                   a.street, a.street2, a.street3, a.city, a.state, a.zip, a.country
              FROM messaging    msg
              JOIN users        u   ON  u.userid    = msg.fromid
              LEFT JOIN (
                    SELECT uci.userid, uci.companyname, uci.phone, uci.fax, uci.email,
                           ui.firstname, ui.lastname, uci.street, uci.street2, uci.street3,
                           uci.city, uci.state, uci.zip, uci.country
                      FROM usercontactinfo      uci
                      JOIN userinfo             ui  ON  ui.userid   = uci.userid
                      JOIN messaging            m   ON  m.fromid    = uci.userid
                                                    AND m.messageid = ".$messageId."
                      LEFT JOIN assignedrights  ar  ON  ar.userid   = uci.userid
                                                    AND userrightid = 11 -- staff
                     WHERE addresstypeid IN (1,3)
                       AND ar.userid IS NULL
                    ORDER BY addresstypeid
                    LIMIT 1
                        )       a   ON  a.userid    = msg.fromid
             WHERE msg.messageId = ".$messageId."
               AND (msg.toid = ".$this->user->userId."
                    OR msg.fromid = ".$this->user->userId.")
        ";

        $data = $this->db->sql_query_params($sql);

        if (count($data) == 1) {
            $msg = reset($data);
        } else {
            $msg = $data;
        }
//echo "<pre>";
//print_r($msg);
//echo "</pre>";
        return $msg;
    }

    public function getLatestMsgOfThread($threadId) {

        $sql = "
            SELECT messageId, threadId, parentId, fromId, fromText, toId,
                   toText, messageType, subjectText, messageText, attachment,
                   attachmentName, status, createdate, modifydate
              FROM messaging
             WHERE threadid = ".$threadId."
               AND (toid = ".$this->user->userId."
                    OR fromid = ".$this->user->userId.")
             ORDER BY messageId DESC
             LIMIT 1
        ";

        $data = $this->db->sql_query_params($sql);
        if (count($data) == 1) {
            $msg = reset($data);
        } else {
            $msg = $data;
        }

        return $msg;
    }

    public function getComplaintThread($offerid, $dealerId) {

        $sql = "
            SELECT messageId, threadId, parentId, fromId, fromText, toId,
                   toText, messageType, subjectText, messageText, attachment,
                   attachmentName, status, createdate, modifydate
              FROM messaging
             WHERE offerid = ".$offerid."
               AND messagetype = '".COMPLAINT."'
               AND (toid = ".$dealerId."
                    OR fromid = ".$dealerId.")
             ORDER BY messageId
        ";

//      echo "<pre>".$sql."</pre>";
        $data = $this->db->sql_query_params($sql);
//echo "<pre>";
//print_r($data);
//echo "</pre>";
        return $data;

    }

    public function getOfferThread($offerid) {

        $sql = "
            SELECT messageId, threadId, parentId, fromId, fromText, toId,
                   toText, messageType, subjectText, messageText, attachment,
                   attachmentName, status, createdate, modifydate
              FROM messaging
             WHERE offerid = ".$offerid."
               AND messagetype = '".OFFERCHAT."'";
        if (!$this->user->isAdmin()) {
            $sql .= "
               AND (toid = ".$this->user->userId."
                    OR fromid = ".$this->user->userId.")";
        }
        $sql .= "
             ORDER BY messageId
        ";

//      echo "<pre>".$sql."</pre>";
        $data = $this->db->sql_query_params($sql);

        $sql = "
            UPDATE messaging
               SET status       = '".READSTATUS."',
                   modifydate   = nowtoint(),
                   modifiedby   = :modifiedby
             WHERE offerid      = ".$offerid."
               AND status       = '".UNREADSTATUS."'
               AND messagetype  = '".OFFERCHAT."'
               AND toid         = ".$this->user->userId;

        $params = array();
        $params['modifiedby']       = $this->user->username;

        $this->db->sql_execute_params($sql, $params);

        return $data;

    }

    public function getOfferInfoAdmin($offerthreadid) {
        $offerinfo = NULL;

        if ($this->user->isAdmin()) {
            $sql = "
                SELECT o.offerto, u.username as to_username,
                       o.offerfrom, u2.username as from_username,
                       o.transactiontype, o.threadid, o.offerid
                  FROM offers   o
                  JOIN users    u   ON u.userid = o.offerto
                  JOIN users    u2  ON u2.userid = o.offerfrom
                 WHERE o.offerid = ".$offerthreadid."
                 ORDER BY offerid DESC
                 LIMIT 1
            ";

            $data = $this->db->sql_query_params($sql);
            $offerinfo = ($data) ? $data[0] : $data;
        }

        return $offerinfo;

    }

    public function getOfferInfo($offerthreadid) {

        $sql = "
            SELECT o.offerstatus, o.offerto, u.username as to_username,
                   o.offerfrom, u2.username as from_username,
                   o.transactiontype, o.threadid, o.offerid
              FROM offers   o
              JOIN users    u   ON u.userid = o.offerto
              JOIN users    u2  ON u2.userid = o.offerfrom
             WHERE o.offerid = ".$offerthreadid."
               AND (o.offerto = ".$this->user->userId."
                    OR o.offerfrom = ".$this->user->userId.")
             ORDER BY offerid DESC
             LIMIT 1
        ";

        $data = $this->db->sql_query_params($sql);
        $offerinfo = ($data) ? $data[0] : $data;

        return $offerinfo;

    }

    public function setNotNew($page, $messageId){
        $sql = "
            UPDATE messaging
               SET modifydate = nowtoint(),
                   modifiedby = :modifiedby
             WHERE messageid = :messageid
               AND createdate = modifydate
               AND status = '".UNREADSTATUS."'
        ";
        $params = array();
        $params['modifiedby']       = $this->user->username;
        $params['messageid']        = $messageId;

        try {
            $this->db->sql_execute_params($sql, $params);
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update status.]");
            $messageId = 0;
        }
    }

    public function updateStatus($page, $messageId, $status){
        $sql = "
            UPDATE messaging
               SET status       = :status,
                   modifydate   = CASE WHEN createdate = modifydate AND '".READSTATUS."' = '".$status."' THEN nowtoint()
                                       ELSE modifydate END::BIGINT,
                   modifiedby   = :modifiedby
             WHERE messageid = :messageid
        ";
        $params = array();
        $params['status']           = $status;
        $params['modifiedby']       = $this->user->username;
        $params['messageid']        = $messageId;

        try {
            $this->db->sql_execute_params($sql, $params);
            $page->messages->addSuccessMsg("Message marked as ".strtolower($status).".");
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update status.]");
            $messageId = 0;
        }
    }

    public function updateOfferStatus($page, $offerId, $status){
        $sql = "
            UPDATE messaging
               SET status       = :status,
                   modifydate   = CASE WHEN createdate = modifydate AND '".READSTATUS."' = '".$status."' THEN nowtoint()
                                       ELSE modifydate END::BIGINT,
                   modifiedby   = :modifiedby
             WHERE offerid = :offerid
               AND toid = :toid
        ";
        $params = array();
        $params['status']           = $status;
        $params['modifiedby']       = $this->user->username;
        $params['offerid']          = $offerId;
        $params['toid']             = $this->user->userId;

        try {
            $this->db->sql_execute_params($sql, $params);
        } catch (Exception $e) {
            $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to update offer and assistance message status.]");
            $messageId = 0;
        }
    }

    public function getUserClasses() {
        $sql = "
            SELECT userclassid, userclassname, sortorder
              FROM userclass
            ORDER BY sortorder
        ";

        $userclass = $this->db->sql_query_params($sql);

        return $userclass;
    }

    public function getUsersInClass($userclassid) {
        $sql = "
            SELECT u.userid, u.username
              FROM users                u
              JOIN userinfo             ui  ON  ui.userid       = u.userid
              JOIN assignedrights       ar  ON  ar.userid       = u.userid
                                            AND ar.userrightid  = 1 -- enabled
             WHERE ui.userclassid = ".$userclassid."
               AND u.userid <> ".$this->user->userId."
            ORDER BY username
        ";

        $users = $this->db->sql_query_params($sql);

        return $users;
    }

    public function getEveryone() {
        $sql = "
            SELECT u.userid, u.username
              FROM users                u
              JOIN assignedrights       ar  ON  ar.userid       = u.userid
                                            AND ar.userrightid  = 1 -- enabled
             WHERE u.userid <> ".$this->user->userId."
            ORDER BY username
        ";

        $users = $this->db->sql_query_params($sql);

        return $users;
    }

    public function deleteMessages($page, $msgids) {

        if (empty($msgids)) {
            $page->messages->addErrorMsg("ERROR: No messages designated.");
        } else {
            $rows = -1;
            $sql = "
                DELETE FROM messaging
                 WHERE messageid IN (".$msgids.")
                   AND fromid = ".$this->user->userId."
                RETURNING *";

            try {
                $rows = $this->db->sql_execute_params($sql);
                $page->messages->addSuccessMsg($rows." message(s) deleted.");
            } catch (Exception $e) {
                $page->messages->addErrorMsg("ERROR: ".$e->getMessage()." [Unable to delete messages.]");
            }
        }

    }

    public function hasAdminMsgsRequiringReply($userid) {
        // Need to sql for admin & limited admins.

        $sql = "
            SELECT count(1) as hasmsgs
              FROM messaging            m
              JOIN assignedrights       ar  ON  ar.userid       = m.fromid
                                            AND ar.userrightid IN (2)
              LEFT JOIN messaging       p   ON  p.parentid      = m.messageid
             WHERE p.messageid IS NULL
               AND m.toid           = ".$userid."
               AND m.replyrequired  = 1
        ";

//        echo "<pre>".$sql."</pre>";
        $hasmsgs = $this->db->get_field_query($sql);

        return $hasmsgs;
    }

    public function hasUnreadMessages($userid) {
        $todate     = strtotime("now");
        $fromdate   = strtotime("-30 days");
        $sql = "
            SELECT count(1) as hasunreadmsgs
              FROM messaging
             WHERE toid         = ".$userid."
               AND status       = '".UNREADSTATUS."'
               AND modifiedby IS NULL
               AND messagetype IN ('".EMAIL."', '".BULKMAIL."', '".OFFERCHAT."', '".COMPLAINT."')
               AND createdate BETWEEN ".$fromdate." AND enddatetime(".$todate.")
        ";

//        echo "<pre>".$sql."</pre>";
        $hasunreadmsgs = $this->db->get_field_query($sql);

        return $hasunreadmsgs;
    }

    public function getAdminDepartments($dept = NULL) {
        $where = (empty($dept)) ? "" : " WHERE departmentid IN (".$dept.")";
        $sql = "
            SELECT departmentid, department, username, sortorder
              FROM admindepartments
            ".$where."
            ORDER BY sortorder
        ";

        $depts = $this->db->sql_query_params($sql);

        return $depts;
    }

    public function sendExternalEmail($toId, $subjectText, $messageText) {
        $sql = "
            SELECT email,
                   CASE WHEN addresstypeid = 2 THEN 99
                        ELSE addresstypeid END AS sortorder
              FROM usercontactinfo
             WHERE userid = ".$toId."
               AND email IS NOT NULL
               AND length(email) > 0
            ORDER BY 2
            LIMIT 1
        ";
//        echo "<pre>".$sql."</pre>";
        $toemail = $this->db->get_field_query($sql);
        if (!empty($toemail)) {
            sendEmail($toemail, $subjectText, $messageText);
        }
    }

    public function sendExternalSMS($toId, $messageText) {
        global $CFG;

        $sql = "
            SELECT emailphone
              FROM notification_preferences
             WHERE userid = ".$toId."
               AND emailphone IS NOT NULL
               AND length(emailphone)   > 0
               AND isactive             = 1
               AND notification_type    = '".SMSNOTIFICATIONTYPE."'
            LIMIT 1
        ";

//        echo "<pre>".$sql."</pre>";
//        echo "<pre>".$messageText."</pre>";
        $tophone = $this->db->get_field_query($sql);
        if (!empty($tophone)) {
            if (isset($CFG->redirecttoemail) && $CFG->redirecttoemail) {
                $subject    = "Redirected SMS Message";
                $message    = "[DIVERTED - ".$tophone."]<br>".$messageText;
                $this->sendExternalEmail($toId, $subject, $message);
            } else {
                sendSMS($toId, $tophone, $messageText);
            }
        }
    }

}

?>