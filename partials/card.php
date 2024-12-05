<?php if (isset($data)) : ?>
    <div class="card">
        <img class="card-img-top" src="<?php se($data, "logo_url"); ?>" alt="Team logo" style="max-width: 300px;">
        <div class="card-body">
            <h5 class="card-title text-center font-weight-bold">
                <?php se($data, "name"); ?> (<?php se($data, "code"); ?>)
            </h5>
            <?php if (isset($data["wins"]) || isset($data["losses"])) : ?>
            <h6 class="card-subtitle mb-2 text-body text-center">
                Record: <?php se($data, "wins"); ?>-<?php se($data, "losses"); ?> (<?php echo ((float)se($data, "win_percentage", "", false) * 100); ?>%)
            </h6>
            <?php endif; ?>
            <div class="card-text">
            <?php se($data, "conference"); ?>ern Conference
            <?php if (isset($data["conference_rank"])) : ?>- Rank: <?php se($data, "conference_rank"); ?> <?php endif; ?>
                <br>
                <?php se($data, "division"); ?> Division
                <?php if (isset($data["division_rank"])) : ?>- Rank: <?php se($data, "division_rank"); ?> <?php endif; ?>
            </div>
            <?php if (isset($data["home_record"]) || isset($data["away_record"]) || isset($data["streak"]) || isset($data["last_10"])) : ?>
            <div class="card-text">
                Home Record: <?php se($data, "home_record"); ?> 
                <br>
                Away Record: <?php se($data, "away_record"); ?>
            </div>
            <div class="card-text">
                <br>
                Streak: <?php se($data, "streak"); ?> 
                <br>
                Last 10: <?php se($data, "last_10"); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif;