<?php

defined('JPATH_BASE') or die;

$d = $displayData;

?>

<div class="">
    <?php
    for ($i=0; $i<count($d->label); $i++) {
        if ($d->existsRating === $d->values[$i]) {?>
            <a name="" href="#void" style="text-decoration: underline;" class="<?php echo $d->values[$i]; ?>" id="<?php echo $d->rowId; ?>"><?php echo $d->label[$i] . " (<span class='valueItem'>"; echo $d->numValues[$i] . "</span>)" ?> </a><br>
        <?php } else {
        ?>
            <a href="#void" name="<?php echo $d->values[$i] . $d->rowId; ?>" class="<?php echo $d->values[$i]; ?>" id="<?php echo $d->rowId; ?>"><?php echo $d->label[$i] . " (<span class='valueItem'>"; echo $d->numValues[$i] . "</span>)" ?></a><br>
    <?php }} ?>
</div>
