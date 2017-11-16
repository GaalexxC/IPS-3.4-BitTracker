<?php

$INDEX[] = "ALTER TABLE bitracker_files ADD FULLTEXT(file_desc)";
$INDEX[] = "ALTER TABLE bitracker_files ADD FULLTEXT(file_name)";
