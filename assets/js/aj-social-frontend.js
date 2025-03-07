var aj_social_textbox;
var aj_social_workcanvas = new fabric.Canvas("ajsocial-canvas-work");
var aj_social_canvas = new fabric.Canvas("ajsocial-canvas");
var aj_social_timeout;





function ajsocial_Designer() {
    width = document.getElementById('ajsocial-widthInput').value || 15;
    text = document.getElementById('ajsocial-textInput').value.trim() || 'Dein_Text';

    if (aj_social_timeout) {
        clearTimeout(aj_social_timeout);
    }
    aj_social_timeout = setTimeout(function () {
        ajsocial_createSVG(text, width);
    }, 200);
}

function ajsocial_createSVG(text, widthCM) {


    let textToSVGObject;
    let ajFontLoaded = false;

    let widthPx = convertUnitToPixel(widthCM);

    let aj_social_svgDiv = document.getElementById("ajsocial-can-svg");
    aj_social_svgDiv.innerHTML = "";

    const fontSize = ajsocial_icon_settings.fontsize ?? 100; // Schriftgröße
    const iconPath = `/wp-content/plugins/aj-social-for-woo/assets/social-svg/${ajsocial_icon_path}.svg`; // Pfad zum Icon
    const iconWidth = ajsocial_icon_settings.iconwidth ?? 115; // Breite des Icons in px
    const iconHeight = ajsocial_icon_settings.iconheight ?? 115; // Höhe des Icons in px
    const spacing = ajsocial_icon_settings.spacing ?? 10; // Abstand zwischen Icon und Text

    let maxWidth = 0,
        totalHeight = 0,
        svgcontent = "";

    // Annahme: Der Text ist eine einfache Zeichenkette ohne Zeilenumbrüche

    var selectedFontName = document.getElementById('ajsocial-font').value;

    // Suche nach der Schriftart im ajsocial_fontsData Array
    var selectedFont = ajsocial_fontsData.find(function (font) {
        return font.name === selectedFontName;
    });

    if (selectedFont) {
        // Prüfen, ob die Schriftart fett (bold) ist
        var ajsocial_fontPath = `/wp-content/plugins/aj-vinyl-for-woo/assets/fonts/${selectedFont.name}`;

        // Wenn die Schriftart fett ist, füge '-bold' zum Dateinamen hinzu
        if (selectedFont.bold) {
            ajsocial_fontPath += ""; // -bold an den Pfad anhängen
        }

        ajsocial_fontPath += ".woff"; // Füge das Dateiformat hinzu

        // Lade die Schriftart mit TextToSVG
        TextToSVG.load(ajsocial_fontPath, function (err, textToSVG) {
            if (err) {
                console.error("Fehler beim Laden der Schriftart:", err);
                return;
            }




            // Optional: Speichere das TextToSVG-Objekt für spätere Verwendung
            textToSVGObject = {
                textToSVG: textToSVG,
                text: text,
            };

            // Setze einen Indikator, dass die Schriftart erfolgreich geladen wurde
            ajFontLoaded = true;
        });
    }



    ajsocial_load();

    function ajsocial_load() {
        if (!ajFontLoaded) {
            setTimeout(function () {
                ajsocial_load();
            }, 10);
            return;
        }

        // Lade das Icon (falls benötigt)
        fabric.loadSVGFromURL(iconPath, function (iconObjects, iconOptions) {
            const icon = fabric.util.groupSVGElements(iconObjects, iconOptions);

            // Berechne die Breite aller Textzeilen

            let textToSVG = textToSVGObject.textToSVG;
            let line = textToSVGObject.text;
            let options = {
                x: 0,
                y: totalHeight,
                fontSize: fontSize || 100,
                anchor: "top left",
                attributes: { fill: "black" },
            };
            let metrics = textToSVG.getMetrics(line, options);
            maxWidth = Math.max(maxWidth, metrics.width);


            // Erstelle das SVG für den Text

            textToSVG = textToSVGObject.textToSVG;
            line = textToSVGObject.text;



            options = {
                x: iconWidth + spacing, // Text beginnt rechts neben dem Icon
                y: totalHeight,
                fontSize: fontSize || 100,
                anchor: "top left",
                attributes: { fill: "black" },
            };

            metrics = textToSVG.getMetrics(line, options);
            svgcontent += textToSVG.getPath(line, options);
            totalHeight += metrics.height;


            // Zentriere den Text vertikal zum Icon
            const iconCenterY = iconHeight / 2;
            const textCenterY = totalHeight / 2;
            const verticalOffset = iconCenterY - textCenterY;

            svgcontent = `<g transform="translate(0, ${verticalOffset})">${svgcontent}</g>`;

            // Füge das Icon hinzu
            fabric.loadSVGFromURL(iconPath, function (iconObjects, iconOptions) {
                let iconGroup = fabric.util.groupSVGElements(iconObjects);
                iconGroup.set({ left: 0, top: 0, padding: 0 });
                iconGroup.scaleToWidth(iconWidth);
                iconGroup.scaleToHeight(iconHeight);

                // SVG finalisieren
                let svg = `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${maxWidth + iconWidth + spacing}" height="${Math.max(totalHeight, iconHeight)}">`;
                svg += `<g>${iconGroup.toSVG()}${svgcontent}</g>`;
                svg += "</svg>";

                aj_social_svgDiv.innerHTML = svg;


                if (aj_social_textbox) {
                    aj_social_workcanvas.remove(aj_social_textbox);
                    delete aj_social_textbox;
                }

                fabric.loadSVGFromString(svg, function (objects, options) {
                    let loadedObject = fabric.util.groupSVGElements(objects);
                    loadedObject.set({ left: 0, top: 0, padding: 0, });

                    // Füge das Objekt zum unsichtbaren Canvas hinzu
                    aj_social_workcanvas.clear();
                    aj_social_workcanvas.add(loadedObject);
                    aj_social_workcanvas.renderAll();
                    aj_social_textbox = loadedObject;

                    // Berechne das SVG auf dem sichtbaren Canvas
                    setTimeout(() => {
                        ajsocial_calcSize(widthPx, widthCM, svg);
                    }, 50);
                });
            });
        });
    }
}

function ajsocial_calcSize(widthPx, widthCM, svg) {
    let heightInCm;
    let heightPx;
    let zoom;

    // Bounding Box des SVG-Objekts vom unsichtbaren Canvas (textbox)
    var svgBoundingBox = aj_social_textbox.getBoundingRect();

    //  berechne die höhe aus der Breite

    heightPx = (widthPx / svgBoundingBox.width) * svgBoundingBox.height;



    // Setze die Höhe basierend auf der gewünschten Höhe (heightPx)
    if (svgBoundingBox.height !== heightPx) {
        // Berechne den Zoom-Faktor für die Skalierung
        zoom = heightPx / svgBoundingBox.height;
        aj_social_textbox.scaleToHeight(heightPx, true);

        // Aktualisiere die neue Höhe und Breite basierend auf der Skalierung
        svgBoundingBox.height = heightPx;
        svgBoundingBox.width = aj_social_textbox.getBoundingRect().width;
    }

    let canvas_new_width;
    let canvas_new_height;
    canvas_new_width = document.getElementById("ajsocial-canvas-container").offsetWidth;


    canvas_new_height = (canvas_new_width / widthPx) * heightPx;
    if (canvas_new_height > 200) {
        canvas_new_width = (200 / canvas_new_height) * canvas_new_width;
        canvas_new_height = 200;
    }

    // Setze die Breite des sichtbaren Canvas basierend auf der neuen Breite des SVG
    aj_social_canvas.setWidth(canvas_new_width);
    aj_social_canvas.setHeight(canvas_new_height);


    // Lade das vorbereitete SVG in das sichtbare Canvas
    fabric.loadSVGFromString(svg, function (objects, options) {
        let visibleObject = fabric.util.groupSVGElements(objects );


        // Skalierung des SVG auf die Höhe des sichtbaren Canvas
        visibleObject.scaleToHeight(aj_social_canvas.height, false);
        visibleObject.setCoords(); // Aktualisiere die Koordinaten nach der Skalierung

        // Zentriere das SVG innerhalb des sichtbaren Canvas
        visibleObject.set({
            left: (aj_social_canvas.getWidth() - visibleObject.getScaledWidth()) / 2,
            top: (aj_social_canvas.getWidth() - visibleObject.getScaledWidth()) /2,
            originX: "left",
            originY: "top",
            hasControls: false,
            hasBorders: false,
            hasRotatingPoint: false,
            lockMovementX: true,
            lockMovementY: true,
            lockRotation: true,
        });

        // Füge das skalierte SVG zum sichtbaren Canvas hinzu
        aj_social_canvas.clear();

        aj_social_canvas.add(visibleObject);

        ajsocial_addKaroBackground(aj_social_canvas, 10);
        aj_social_canvas.renderAll();
    });




    // Optional: Aktualisiere die Höhe in einem Eingabefeld (falls benötigt)
    heightInCm = ajround(
        ((widthPx / svgBoundingBox.width) * svgBoundingBox.height * 2.54) / 96,
        1
    );

    // Fehlerbehandlung
    let error = ""; // Variable für die Fehlermeldung

    // Fehlerbedingungen:
    if (heightInCm < 2.60) {
        error = "Die Höhe sollte mindestens 2,60 cm betragen. Bitte ändern Sie die Größe oder verringern Sie die Anzahl der Zeichen.";
    }
    // Fehler anzeigen:
    const errorContainer = document.getElementById('aj-social-error-container');

    ajsocial_clearErrorMessage(errorContainer);

    if (error) {
        // Fehlernachricht im Container anzeigen
        showErrorMessage(errorContainer, error);
    }


    document.getElementById("ajsocial-heightOutput").innerHTML = widthCM + " cm Breite x " + heightInCm.toFixed(2).replace('.', ',') + " cm Höhe";
    document.getElementById("ajsocial-height-output").value = heightInCm;
    ajsocial_updatePrice();


}

function ajsocial_addKaroBackground(canvas, squareSize = 10) {
    // Erstelle ein neues Canvas-Element, das als Muster verwendet wird
    const patternCanvas = document.createElement("canvas");
    const patternContext = patternCanvas.getContext("2d");

    // Setze die Größe des Musters
    patternCanvas.width = squareSize;
    patternCanvas.height = squareSize;

    // Zeichne das Karomuster (Horizontale und vertikale Linien)
    patternContext.strokeStyle = "#e0e0e0"; // Farbe der Karo-Linien
    patternContext.lineWidth = 1; // Breite der Karo-Linien

    // Horizontale Linie
    patternContext.beginPath();
    patternContext.moveTo(0, 0);
    patternContext.lineTo(squareSize, 0);
    patternContext.stroke();

    // Vertikale Linie
    patternContext.beginPath();
    patternContext.moveTo(0, 0);
    patternContext.lineTo(0, squareSize);
    patternContext.stroke();

    // Verwende das Canvas als Muster für den Hintergrund
    const pattern = new fabric.Pattern({
        source: patternCanvas,
        repeat: "repeat", // Das Muster wird wiederholt
    });

    // Setze das Muster als Hintergrund des Canvas
    canvas.setBackgroundColor(
        { source: patternCanvas, repeat: "repeat" },
        canvas.renderAll.bind(canvas)
    );
}

function ajround(value, precision = 0) {
    const factor = Math.pow(10, precision + 1); // Eine Stufe höher für präzisere Zwischenberechnung
    const tempValue = Math.round(value * factor) / 10; // Zwischenwert auf exakt eine Dezimalstelle mehr runden
    return Math.round(tempValue) / Math.pow(10, precision); // Endwert runden und zurückgeben
}

function convertUnitToPixel(value) {
    var dpi = 96;
    var unitFac = 2.54;
    return ajround((value * dpi) / unitFac, 0);
}
function convertPixelToUnit(value, aufrund = 1) {
    var dpi = 96;
    var unitFac = 2.54;
    return ajround((value * unitFac) / dpi, aufrund);
}

// Funktion, die die Farben im zweiten Dropdown basierend auf dem Finish filtert
function ajsocial_filterColors() {
    var finish = document.getElementById('ajsocial-finishInput').value; // Das ausgewählte Finish
    var colorDropdown = document.getElementById('ajsocial-colorInput'); // Das Farb-Dropdown

    // Leere das Farb-Dropdown
    colorDropdown.innerHTML = '';

    // Filtern der Farben basierend auf der Auswahl des Finish
    var filteredColors = ajsocial_colorsData.filter(function (item) {
        return item.finish === finish;
    });

    // Füge die gefilterten Farben zum Dropdown hinzu
    filteredColors.forEach(function (item) {
        var option = document.createElement('option');
        option.value = item.color;
        option.textContent = item.color;
        colorDropdown.appendChild(option);
    });

    // Optional: Setze den Wert des Farb-Dropdowns auf den ersten Eintrag, falls nötig
    if (filteredColors.length > 0) {
        colorDropdown.value = filteredColors[0].color;
    }
}

function ajsocial_updatePrice() {
    const widthSelect = document.getElementById("ajsocial-widthInput");
    const priceDisplay = document.getElementById("aj-social-price");

    // Holen der ausgewählten Breite
    const selectedWidth = widthSelect.value;

    let discount = ajsocial_getDiscount();



    // Prüfen, ob die Breite in der Preistabelle existiert
    if (ajsocial_priceData[selectedWidth]) {
        // Preis anzeigen
        let normalprice = ajsocial_priceData[selectedWidth];


        if (discount === 0) {
            priceDisplay.textContent = normalprice.toFixed(2).replace('.', ',') + ' €'; // Zwei Dezimalstellen
        } else {
            let discounted_price = normalprice * (1 - discount);
            priceDisplay.innerHTML = `
                <span style="text-decoration: line-through; color: #888;">
                    ${normalprice
                    .toFixed(2)
                    .replace(".", ",")}&nbsp;€
                </span> 
                <span style="color: red; font-weight: bold; padding-left: 10px;">
                    ${ajround(discounted_price, 2).toFixed(2).replace(".", ",")}&nbsp;€
                </span>
            `;

        }

    } else {
        // Wenn keine gültige Breite gewählt wurde
        priceDisplay.textContent = "-";
    }
}

// Zeigt die Fehlermeldung im Container an
function ajsocial_showErrorMessage(container, message) {
    container.textContent = message;
    container.style.color = 'red';
    container.style.fontSize = '16px';
}
function ajsocial_clearErrorMessage(container) {
    container.textContent = '';
}


function ajsocial_getDiscount() {
    let quantityInput = document.querySelector("input.qty"); // Eingabefeld für Menge

    let quantity = parseInt(quantityInput.value);
    let discount = 0;

    // Rabatt basierend auf den definierten Regeln finden
    for (let i = 0; i < ajsocial_discountsData.length; i++) {
        if (quantity >= ajsocial_discountsData[i].quantity) {
            discount = ajsocial_discountsData[i].discount;
        }
    }

    return discount;
}

function aj_social_inputchanger() {
    let quantityInput = document.querySelector("input.qty");
    quantityInput.addEventListener("input", function () {



        setTimeout(function () {
            ajsocial_updatePrice();

        }, 10);
    });

    let quantityButtons = document.querySelectorAll(
        ".quantity .plus, .quantity .minus"
    );
    quantityButtons.forEach((button) =>
        button.addEventListener("click", function () {

            setTimeout(function () {
                ajsocial_updatePrice();
            }, 10);
        })
    );
}

document.addEventListener("DOMContentLoaded", function () {
    // Prüfen, ob das Feld existiert
    let feldcheck = document.getElementById('ajsocial-finishInput');
    if (feldcheck) {
        ajsocial_Designer();
        ajsocial_filterColors();
        aj_social_inputchanger();

    }


});
