##
## PlanZ email queue changes.
##
## Created by BC Holmes
##

ALTER TABLE EmailQueue ADD COLUMN `name` varchar(255), ADD COLUMN `emailreplyto` varchar(255);
ALTER TABLE EmailHistory ADD COLUMN `name` varchar(255), ADD COLUMN `emailreplyto` varchar(255);

INSERT INTO PatchLog (patchname) VALUES ('98ZED_email_queue_revisions.sql');
