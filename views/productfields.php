<div class="aj-social-order-container">

    <div id="ajsocial-canvas-container">
        <canvas id="ajsocial-canvas"></canvas>
    </div>

    <div id="ajsocial-heightOutput" style="text-align: center; margin: 10px; font-size: 16px; font-weight: bold; color:black;"></div>
    <p class="ajpreview-info" style="text-align: center;">Die Vorschau dient lediglich der visuellen Darstellung und entspricht nicht exakt den tatsächlichen Farben und Schriften.</p>
    <p class="ajpreview-info" style="text-align: center;">Die Höhenangabe ist nur ein Schätzwert und kann leicht variieren.</p>
    <div id="aj-social-error-container" style="text-align: center;"></div>


    <div class="ajsocial-can-work-box">
        <div id="ajsocial-can-svg"></div>
        <div id="ajsocial-canvas-container">
            <canvas id="ajsocial-canvas-work"></canvas>
        </div>

    </div>

    <div class="ajsocial-text-container">
        <label for="ajsocial-textInput">Text:</label>
        <input type="text" id="ajsocial-textInput" name="aj-social-text" placeholder="Dein_Text" oninput="ajsocial_Designer()" />
    </div>

    <div class="aj-social-dropdown-container">
        <!-- Dropdown für Schriftart -->
        <label for="ajsocial-font">Schriftart:</label>
        <select name="aj-social-font" id="ajsocial-font" onchange="ajsocial_Designer()">
            <?php
            // Schriftarten-Optionen generieren
            if (!empty($fontdata)) {
                foreach ($fontdata as $font) {
                    echo '<option value="' . esc_attr($font['name']) . '">' . esc_html($font['name']) . '</option>';
                }
            } else {
                echo '<option value="">Keine Schriftarten verfügbar</option>';
            }
            ?>
        </select>

    </div>


    <!-- Eingabefelder für Höhe und Breite -->
    <div class="aj-social-dimension-container">

        <div class="aj-social-dimension-dropdown">
            <label for="ajsocial-widthInput">Breite (cm):</label>
            <select name="aj-social-width" id="ajsocial-widthInput" onchange="ajsocial_Designer()">


                <?php

                // Überprüfen, ob Preisstabelle existiert und die Optionen generieren
                if (!empty($widthdata)) {
                    foreach ($widthdata as $width => $price) {
                        echo '<option value="' . esc_attr($width) . '">' . esc_html($width) . ' cm' . '</option>';
                    }
                } else {
                    echo '<option value="">Keine Breiten verfügbar</option>';
                }
                ?>
            </select>
        </div>
    </div>



    <!-- Dropdown für Oberfläche -->
    <div class="aj-social-dropdown-container">
        <label for="ajsocial-finishInput">Oberfläche:</label>
        <select name="aj-social-surface" id="ajsocial-finishInput" onchange="ajsocial_filterColors()">
            <option value="Glanz">Glänzend</option>
            <option value="Matt">Matt</option>
        </select>
    </div>

    <!-- Dropdown für Farbe -->
    <div class="aj-social-dropdown-container">
        <label for="ajsocial-colorInput">Farbe:</label>
        <select name="aj-social-color" id="ajsocial-colorInput">
            <!-- Die Farben werden durch JavaScript dynamisch befüllt -->
        </select>
    </div>

    <script>
        var ajsocial_colorsData = <?php echo $colors_json; ?>; // Farb- und Finish-Daten als JavaScript-Objekt
        var ajsocial_fontsData = <?php echo json_encode($fontdata); ?>;
        var ajsocial_icon_path = "<?php echo $icon_path; ?>";
        var ajsocial_icon_settings = <?php echo json_encode($iconSettings); ?>;
        var ajsocial_priceData = <?php echo json_encode($widthdata); ?>;
        var ajsocial_discountsData = <?php echo $discountData; ?>;
    </script>


    <!-- Divider (Strich) nach den Eingabefeldern -->
    <div class="aj-social-divider"></div>

    <!-- Preisbereich (rechts ausgerichtet) -->
    <div class="aj-social-price-container">
        <div class="aj-social-price-line">
            <div class="aj-social-price-text">Preis:</div>
            <div id="aj-social-price" class="aj-social-price"><strong>4,49 €</strong></div>
        </div>
        <div class="aj-social-price-per-item">pro stk.</div>
        <?php

        if (has_filter('aj_vinyl_legal_info')) {
            // Wenn der Filter registriert ist, wende ihn an
            apply_filters('aj_vinyl_legal_info', $value);
        }
        ?>
      
    </div>





    <input type="hidden" id="ajsocial-height-output" name="aj-social-height">



</div>