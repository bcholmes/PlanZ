<?php
// Copyright (c) 2011-2021 Peter Olszowka. All rights reserved. See copyright document for more details.
global $participant, $message_error, $message2, $congoinfo, $title;
$title = "Staff Overview";
require_once('StaffCommonCode.php');
require_once('con_data.php');
require_once('SiteHealth.php');
staff_header($title,  true);
?>

<div class="row">
<div class="col-lg-8">
<div class="card mt-2">
    <div class="card-header">
        <h2><?php echo CON_NAME; ?></h2>
        <p>
<?php
        $conData = ConData::fromEnvironmentDefinition();
        echo $conData->startDate->format('D, j M Y');
        echo ' - ';
        echo $conData->endDate->format('D, j M Y');
        if (DEFAULT_DURATION == "1:00" || DEFAULT_DURATION == "60") {
            $DEFAULT_DURATION = "1 hour";
        } else {
            $DEFAULT_DURATION = "1 and a quarter hours";
        }
        echo " (".CON_NUM_DAYS." days)";
?>
        </p>
    </div>

<?php

if (!populateCustomTextArray()) {
    $message_error = "Failed to retrieve custom text. " . $message_error;
    RenderError($message_error);
    exit();
}
echo "    <div class=\"card-body\">";
echo fetchCustomText("staff_overview");
echo "    </div><!-- close card body -->";
?>
</div><!-- close card top level -->

</div>
<div class="col-lg-4">

<hr class="d-lg-none" />

<div class="card mt-2">
<?php
$query = "SELECT * FROM Phases WHERE current=1 AND implemented=1 ORDER BY display_order";
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // Should have exited already
}
echo "    <div class=\"card-header\">\n";
echo "<h5>Phases of the System</h5> \n";
echo "    </div><!-- close card header -->\n";
echo "    <div class=\"card-body\">\n";
echo "Phases that are on: \n";
echo "<ul>\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo '<li>' . $row["phasename"] . ' - ' . $row["notes"] . '</li>' . "\n";
}
mysqli_free_result($result);
echo "</ul>\n";

$query = "SELECT * FROM Phases WHERE current=0 AND implemented=1 AND (module_id is null OR module_id in (select id from module where is_enabled = 1)) ORDER BY display_order";
if (!$result = mysqli_query_exit_on_error($query)) {
    exit(); // Should have exited already
}
echo "Phases that are off: <br /> \n";
echo "<ul>\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo '<li>' . $row["phasename"] . ' - ' . $row["notes"] . '</li>' . "\n";
}
mysqli_free_result($result);
echo "</ul>\n";
echo "    </div><!-- close card body -->\n";

?>
</p>
</div><!-- close card top level -->

</div>
</div>

<div class="row">
<div class="col-lg-8">
<div class="card mt-2">
    <div class="card-header">
        <h5><?php echo CON_NAME; ?> Status</h5>
    </div>
    <div class="card-body">
<?php
    $healthCheck = new SiteHealth;
    $healthCheck->findSiteProblems();
    echo $healthCheck->renderSiteStatus();
?>
    </div><!-- close card body -->
</div><!-- close card top level -->

</div>
</div>

<?php staff_footer(); ?>
