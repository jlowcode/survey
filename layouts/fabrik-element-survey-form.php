<?php

defined('JPATH_BASE') or die;

$d = $displayData;

?>

<div class="survey-data">
    <?php
        for ($i=0; $i<count($d->labels); $i++) {
            ?><?php echo $d->labels[$i] . " (" . $d->numValues[$i] . ")<br>";?>
        <?php
        }
        ?>
</div>
