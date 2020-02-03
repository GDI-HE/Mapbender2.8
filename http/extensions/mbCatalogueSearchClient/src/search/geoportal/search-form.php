<?php $n = $params['name']; ?>
<div class="search-extended -js-search-extended">
  <form class="-js-extended-search-form" data-search="<?php echo $n ?>">
    <div class="filter-title">Sortieren nach:</div>
    <div class="inline">
      <input type="radio" name="orderBy" value="rank" id="<?php echo $n ?>-orderBy-rank" checked="">
      <label for="<?php echo $n ?>-orderBy-rank">Nachfrage</label>
    </div>
    <div class="inline">
      <input type="radio" name="orderBy" value="title" id="<?php echo $n ?>-orderBy-title">
      <label for="<?php echo $n ?>-orderBy-title">Alphabetisch</label>
    </div>
    <div class="inline">
      <input type="radio" name="orderBy" value="id" id="<?php echo $n ?>-orderBy-id">
      <label for="<?php echo $n ?>-orderBy-id">Ident. Nummer</label>
    </div>
    <div class="inline">
      <input type="radio" name="orderBy" value="date" id="<?php echo $n ?>-orderBy-date">
      <label for="<?php echo $n ?>-orderBy-date">Letzte Änderung</label>
    </div>
    <ul class="search-tabs -js-tabs">
      <li class="tab-item -js-tab-item active" data-id="<?php echo $n ?>-search-extended-where">Wo?</li>
      <li class="tab-item -js-tab-item" data-id="<?php echo $n ?>-search-extended-when">Wann?</li>
      <li class="tab-item -js-tab-item" data-id="<?php echo $n ?>-search-extended-theme">Themen</li>
      <li class="tab-item -js-tab-item" data-id="<?php echo $n ?>-search-extended-provider">Anbieter</li>
      <li class="tab-item -js-tab-item" data-id="<?php echo $n ?>-search-extended-what">Was?</li>
    </ul>
    <div id="<?php echo $n ?>-search-extended-where" class="-js-content search-filter active">
      <div class="filter-title">Räumliche Einschränkung</div>
      <div class="inline">
        <input type="checkbox" name="searchBbox" id="<?php echo $n ?>-searchBbox">
        <label for="<?php echo $n ?>-searchBbox">räumliche Eingrenzung aktivieren</label>
      </div>
      <div class="inline">
        <input type="radio" name="searchTypeBbox" value="intersects" id="<?php echo $n ?>-searchTypeBbox-intersects">
        <label for="<?php echo $n ?>-searchTypeBbox-intersects">angeschnitten</label>
      </div>
      <div class="inline">
        <input type="radio" name="searchTypeBbox" value="outside" id="<?php echo $n ?>-searchTypeBbox-outside">
        <label for="<?php echo $n ?>-searchTypeBbox-outside">außerhalb</label>
      </div>
      <div class="inline">
        <input type="radio" name="searchTypeBbox" value="inside" id="<?php echo $n ?>-searchTypeBbox-inside">
        <label for="<?php echo $n ?>-searchTypeBbox-inside">komplett innerhalb</label>
      </div>
      <div class="map-wrapper"></div>
      <?php if (isset($params['value']['form']) && ($map = $params['value']['form']['map'])) { ?>
          <script type="text/javascript">
              var mapConf = mapConf || {};
              mapConf['<?php echo $n ?>'] = <?php echo json_encode($params['value']['form']['map']) ?>;
              mapConf['<?php echo $n ?>']['mapId'] = "<?php echo $n ?>-map";
          </script>
      <?php } ?>
    </div>
    <div id="<?php echo $n ?>-search-extended-when" class="-js-content search-filter">
      <div class="filter-title">Zeitliche Einschränkung</div>
      <div class="filter-group">Veröffentlichungsdatum</div>
      <div class="inline">
        <label for="<?php echo $n ?>-regTimeBegin">Datum von:</label>
        <input class="-js-datepicker hasDatepicker" type="text" size="15" name="regTimeBegin" id="<?php echo $n ?>-regTimeBegin">
      </div>
      <div class="inline">
        <label for="<?php echo $n ?>-regTimeEnd">Datum bis:</label>
        <input class="-js-datepicker hasDatepicker" type="text" size="15" name="regTimeEnd" id="<?php echo $n ?>-regTimeEnd">
      </div>
      <div class="filter-group">Datenaktualität</div>
      <div class="inline">
        <label for="<?php echo $n ?>-timeBegin">Datum von:</label>
        <input class="-js-datepicker hasDatepicker" type="text" size="15" name="timeBegin" id="<?php echo $n ?>-timeBegin">
      </div>
      <div class="inline">
        <label for="<?php echo $n ?>-timeEnd">Datum bis:</label>
        <input class="-js-datepicker hasDatepicker" type="text" size="15" name="timeEnd" id="<?php echo $n ?>-timeEnd">
      </div>
    </div>

    <div id="<?php echo $n ?>-search-extended-theme" class="-js-content search-filter">
      <div class="filter-title">Klassifikationen</div>
      <div class="inline inspire">
        <div class="filter-group">Inspire Themen</div>
        <img title="Inspire" src="./images/inspire_tr_36.png">
        <select class="selectCat" size="5" name="inspireThemes" id="<?php echo $n ?>-inspireThemes" multiple=""><option value="5" title="1.5 Adressen">Adressen</option>
          <option value="26" title="3.13 Atmosphärische Bedingungen">Atmosphärische Bedingungen</option>
          <option value="24" title="3.11 Bewirtschaftungsgebiete/Schutzgebiete/geregelte Gebiete und Berichterstattungseinheiten">Bewirtschaftungsgebiete/Schutzgebiete/geregelte Gebiete und Berichterstattungseinheiten</option>
          <option value="30" title="3.17 Biogeografische Regionen">Biogeografische Regionen</option>
          <option value="16" title="3.3 Boden">Boden</option>
          <option value="11" title="2.2 Bodenbedeckung">Bodenbedeckung</option>
          <option value="17" title="3.4 Bodennutzung">Bodennutzung</option>
          <option value="33" title="3.20 Energiequellen">Energiequellen</option>
          <option value="6" title="1.6 Flurstücke/Grundstücke (Katasterparzellen)">Flurstücke/Grundstücke (Katasterparzellen)</option>
          <option value="25" title="3.12 Gebiete mit naturbedingten Risiken">Gebiete mit naturbedingten Risiken</option>
          <option value="15" title="3.2 Gebäude">Gebäude</option>
          <option value="3" title="1.3 Geografische Bezeichnungen">Geografische Bezeichnungen</option>
          <option value="2" title="1.2 Geografische Gittersysteme">Geografische Gittersysteme</option>
          <option value="13" title="2.4 Geologie">Geologie</option>
          <option value="18" title="3.5 Gesundheit und Sicherheit">Gesundheit und Sicherheit</option>
          <option value="8" title="1.8 Gewässernetz">Gewässernetz</option>
          <option value="10" title="2.1 Höhe">Höhe</option>
          <option value="1" title="1.1 Koordinatenreferenzsysteme">Koordinatenreferenzsysteme</option>
          <option value="22" title="3.9 Landwirtschaftliche Anlagen und Aquakulturanlagen">Landwirtschaftliche Anlagen und Aquakulturanlagen</option>
          <option value="31" title="3.18 Lebensräume und Biotope">Lebensräume und Biotope</option>
          <option value="29" title="3.16 Meeresregionen">Meeresregionen</option>
          <option value="27" title="3.14 Meteorologisch-geografische Kennwerte">Meteorologisch-geografische Kennwerte</option>
          <option value="34" title="3.21 Mineralische Bodenschätze">Mineralische Bodenschätze</option>
          <option value="12" title="2.3 Orthofotografie">Orthofotografie</option>
          <option value="28" title="3.15 Ozeanografisch-geografische Kennwerte">Ozeanografisch-geografische Kennwerte</option>
          <option value="21" title="3.8 Produktions- und Industrieanlagen">Produktions- und Industrieanlagen</option>
          <option value="9" title="1.9 Schutzgebiete">Schutzgebiete</option>
          <option value="14" title="3.1 Statistische Einheiten">Statistische Einheiten</option>
          <option value="20" title="3.7 Umweltüberwachung">Umweltüberwachung</option>
          <option value="7" title="1.7 Verkehrsnetze">Verkehrsnetze</option>
          <option value="19" title="3.6 Versorgungswirtschaft und staatliche Dienste">Versorgungswirtschaft und staatliche Dienste</option>
          <option value="32" title="3.19 Verteilung der Arten">Verteilung der Arten</option>
          <option value="23" title="3.10 Verteilung der Bevölkerung — Demografie">Verteilung der Bevölkerung — Demografie</option>
          <option value="4" title="1.4 Verwaltungseinheiten">Verwaltungseinheiten</option>
        </select>
        <span class="reset-select -js-reset-select" data-target="<?php echo $n ?>-inspireThemes">Auswahl zurücksetzen</span>
      </div>
      <div class="inline iso">
        <div class="filter-group">ISO19115 Themen</div>
        <select class="selectCat" size="5" name="isoCategories" id="<?php echo $n ?>-isoCategories" multiple="">
          <option value="11" title="Aufklärung/Militär">Aufklärung/Militär</option>
          <option value="17" title="Bauwerke">Bauwerke</option>
          <option value="10" title="Bilddaten/Basiskarte/Landbedeckung">Bilddaten/Basiskarte/Landbedeckung</option>
          <option value="12" title="Binnengewässer">Binnengewässer</option>
          <option value="2" title="Biologie">Biologie</option>
          <option value="8" title="Geowissenschaft">Geowissenschaft</option>
          <option value="16" title="Gesellschaft">Gesellschaft</option>
          <option value="9" title="Gesundheitswesen">Gesundheitswesen</option>
          <option value="3" title="Grenzen">Grenzen</option>
          <option value="6" title="Höhenangaben">Höhenangaben</option>
          <option value="4" title="Klimatologie/Meteorologie/Atmosphäre">Klimatologie/Meteorologie/Atmosphäre</option>
          <option value="1" title="Landwirtschaft">Landwirtschaft</option>
          <option value="14" title="Meere">Meere</option>
          <option value="13" title="Ortsangaben">Ortsangaben</option>
          <option value="15" title="Planungsunterlagen/Kataster">Planungsunterlagen/Kataster</option>
          <option value="7" title="Umwelt">Umwelt</option>
          <option value="19" title="Ver- und Entsorgung/Nachrichtenwesen">Ver- und Entsorgung/Nachrichtenwesen</option>
          <option value="18" title="Verkehrswesen">Verkehrswesen</option>
          <option value="5" title="Wirtschaft">Wirtschaft</option>
        </select>
        <span class="reset-select -js-reset-select" data-target="<?php echo $n ?>-isoCategories">Auswahl zurücksetzen</span>
      </div>
      <div class="inline custom">
        <div class="filter-group">Andere Themen</div>
        <select class="selectCat" size="5" name="customCategories" id="<?php echo $n ?>-customCategories" multiple="">
          <option value="11" title="INSPIRE Monitoring">INSPIRE Monitoring</option>
          <option value="12" title="NGDB">NGDB</option>
        </select>
        <span class="reset-select -js-reset-select" data-target="<?php echo $n ?>-customCategories">Auswahl zurücksetzen</span>
      </div>
    </div>

    <div id="<?php echo $n ?>-search-extended-provider" class="-js-content search-filter">
      <div class="filter-group">Anbieter:</div>
      <select class="selectCat" size="5" name="registratingDepartments" id="<?php echo $n ?>-registratingDepartments" multiple="">
        <option value="69" title="Es wurde noch kein Titel für die Gruppe eingestellt!">AdV</option>
        <option value="38" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Agroscience RLP</option>
        <option value="1006" title="Fa. bespire demo">bespire-demo</option>
        <option value="134" title="Bundesamt für Kartografie und Geodäsie - Geodatenzentrum">BKG - Geodatenzentrum</option>
        <option value="1007" title="BKS-Portal.rlp">BKS-Portal.rlp</option>
        <option value="871" title="Bundesanstalt für Landwirtschaft und Ernährung">BLE</option>
        <option value="699" title="Demogruppe für INSPIRE Proxy Tests">Demogruppe</option>
        <option value="101" title="Technische Zentralstelle DLR">DLR TZ</option>
        <option value="91" title="Dienstleistungszentrum IT der Bundesverwaltung für Verkehr Bau und Stadtentwicklung">DLZ-IT BVBS</option>
        <option value="136" title="European Spatial Infrastructure Network">ESDIN</option>
        <option value="873" title="FOSSGIS 2014">FOSSGIS 2014</option>
        <option value="1008" title="FOSSGIS e.V.">FOSSGIS e.V.</option>
        <option value="883" title="Zentrale Kompetenzstelle für Geoinformation Hessen">GDI-Hessen</option>
        <option value="107" title="Zentrale Stelle Geodateninfrastruktur Saarland">GDI-Saarland</option>
        <option value="135" title="Generaldirektion Kulturelles Erbe">GDKE</option>
        <option value="991" title="Gemeinde Morbach">GEM Morbach</option>
        <option value="90" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Gemeinde Grafschaft</option>
        <option value="83" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Geschäftsstelle GDI-BW</option>
        <option value="82" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Geschäftsstelle GDI-BW</option>
        <option value="84" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Geschäftsstelle GDI-NRW</option>
        <option value="63" title="Geschäfts- und Koordinierungstelle GDI-DE">GKSt. GDI-DE</option>
        <option value="87" title="Koordinierungsstelle GDI-NI beim Landesbetrieb Landesvermessung und Geobasisinformation Niedersachsen (LGN)">Koordinierungsstelle GDI-NI</option>
        <option value="39" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Kreisverwaltung Bernkastel-Wittlich</option>
        <option value="80" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Kreisverwaltung Bitburg-Prüm</option>
        <option value="66" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Kreisverwaltung Trier-Saarburg</option>
        <option value="35" title="Landesbetrieb Mobilität Rheinland-Pfalz">LBM-Zentrale</option>
        <option value="61" title="Landesamt für Umwelt">LfU</option>
        <option value="30" title="Landesamt für Geologie und Bergbau">LGB</option>
        <option value="561" title="Landkreis Ahrweiler">LK Ahrweiler</option>
        <option value="165" title="Landkreis Bernkastel-Wittlich">LK Bernkastel-Wittlich</option>
        <option value="552" title="Landkreis Cochem-Zell">LK Cochem-Zell</option>
        <option value="559" title="Landkreis Mainz-Bingen">LK Mainz-Bingen</option>
        <option value="700" title="Landkreis Neuwied">LK Neuwied</option>
        <option value="170" title="Rhein-Lahn-Kreis">LK Rhein-Lahn-Kreis</option>
        <option value="148" title="Rhein-Pfalz-Kreis">LK Rhein-Pfalz-Kreis</option>
        <option value="194" title="Landkreis Südliche Weinstraße">LK Südliche Weinstraße</option>
        <option value="196" title="Landkreis Vulkaneifel">LK Vulkaneifel</option>
        <option value="708" title="Test_user">lugeo1</option>
        <option value="31" title="Landesamt für Vermessung und Geobasisinformationen ">LVermGeo</option>
        <option value="902" title="Landwirtschaftskammer Rheinland-Pfalz">LWK Rheinland-Pfalz</option>
        <option value="41" title="Ministerium des Innern und für Sport">MDI</option>
        <option value="872" title="Ministerium für Familie, Frauen, Jugend, Integration und Verbraucherschutz ">MFFJIV</option>
        <option value="44" title="Ministerium für Umwelt, Energie, Ernährung und Forsten">MUEEF</option>
        <option value="67" title="Ministerium für Wirtschaft, Klimaschutz, Energie und Landesplanung">MWKEL - Oberste Landesplanungsbehörde</option>
        <option value="950" title="MWKEL - Strahlenschutzvorsorge, Schutz vor natürlichen Strahlungsquellen,  Fernüberwachung kerntechnischer Anlagen">MWKEL - Strahlenschutz</option>
        <option value="34" title="Ministerium für Wirtschaft, Verkehr, Landwirtschaft und Weinbau">MWVLW</option>
        <option value="993" title="Ortsgemeinde Ahrbrück">OG Ahrbrück</option>
        <option value="110" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Albersweiler</option>
        <option value="521" title="Ortsgemeinde Allendorf">OG Allendorf</option>
        <option value="633" title="Ortsgemeinde Almersbach">OG Almersbach</option>
        <option value="409" title="Ortsgemeinde Alsbach">OG Alsbach</option>
        <option value="994" title="Ortsgemeinde Altenahr">OG Altenahr</option>
        <option value="380" title="Ortsgemeinde Altendiez">OG Altendiez</option>
        <option value="723" title="Ortsgemeinde Alterkülz">OG Alterkülz</option>
        <option value="570" title="Ortsgemeinde Altrip">OG Altrip</option>
        <option value="593" title="Ortsgemeinde Anschau">OG Anschau</option>
        <option value="594" title="Ortsgemeinde Arft">OG Arft</option>
        <option value="748" title="Ortsgemeinde Argenthal">OG Argenthal</option>
        <option value="324" title="Ortsgemeinde Arnshöfen">OG Arnshöfen</option>
        <option value="472" title="Ortsgemeinde Arzbach">OG Arzbach</option>
        <option value="971" title="Ortsgemeinde Arzfeld">OG Arzfeld</option>
        <option value="480" title="Ortsgemeinde Attenhausen">OG Attenhausen</option>
        <option value="504" title="Ortsgemeinde Auel">OG Auel</option>
        <option value="378" title="Ortsgemeinde Aull">OG Aull</option>
        <option value="595" title="Ortsgemeinde Baar">OG Baar</option>
        <option value="635" title="Ortsgemeinde Bachenberg">OG Bachenberg</option>
        <option value="363" title="Ortsgemeinde Balduinstein">OG Balduinstein</option>
        <option value="321" title="Ortsgemeinde Bassenheim">OG Bassenheim</option>
        <option value="473" title="Ortsgemeinde Becheln">OG Becheln</option>
        <option value="863" title="Ortsgemeinde Beilingen">OG Beilingen</option>
        <option value="554" title="Ortsgemeinde Beilstein">OG Beilstein</option>
        <option value="579" title="Ortsgemeinde Beindersheim">OG Beindersheim</option>
        <option value="786" title="Ortsgemeinde Belg">OG Belg</option>
        <option value="356" title="Ortsgemeinde Bell">OG Bell</option>
        <option value="724" title="Ortsgemeinde Bell (Hunsrück)">OG Bell (Hunsrueck)</option>
        <option value="725" title="Ortsgemeinde Beltheim">OG Beltheim</option>
        <option value="749" title="Ortsgemeinde Benzweiler">OG Benzweiler</option>
        <option value="995" title="Ortsgemeinde Berg">OG Berg</option>
        <option value="522" title="Ortsgemeinde Berghausen">OG Berghausen</option>
        <option value="454" title="Ortsgemeinde Berlingen">OG Berlingen</option>
        <option value="596" title="Ortsgemeinde Bermel">OG Bermel</option>
        <option value="523" title="Ortsgemeinde Berndroth">OG Berndroth</option>
        <option value="636" title="Ortsgemeinde Berod">OG Berod</option>
        <option value="325" title="Ortsgemeinde Berod bei Wallmerod">OG Berod bei Wallmerod</option>
        <option value="277" title="Ortsgemeinde Betteldorf">OG Betteldorf</option>
        <option value="524" title="Ortsgemeinde Biebrich">OG Biebrich</option>
        <option value="326" title="Ortsgemeinde Bilkheim">OG Bilkheim</option>
        <option value="174" title="Ortsgemeinde Billigheim-Ingenheim">OG Billigheim-Ingenheim</option>
        <option value="263" title="Ortsgemeinde Birgel">OG Birgel</option>
        <option value="702" title="Ortsgemeinde Birken-Honigsessen">OG Birken-Honigsessen</option>
        <option value="384" title="Ortsgemeinde Birkenbeul">OG Birkenbeul</option>
        <option value="585" title="Ortsgemeinde Birkenheide">OG Birkenheide</option>
        <option value="175" title="Ortsgemeinde Birkweiler">OG Birkweiler</option>
        <option value="359" title="Ortsgemeinde Birlenbach">OG Birlenbach</option>
        <option value="637" title="Ortsgemeinde Birnbach">OG Birnbach</option>
        <option value="455" title="Ortsgemeinde Birresborn">OG Birresborn</option>
        <option value="385" title="Ortsgemeinde Bitzen">OG Bitzen</option>
        <option value="278" title="Ortsgemeinde Bleckhausen">OG Bleckhausen</option>
        <option value="571" title="Ortsgemeinde Bobenheim-Roxheim">OG Bobenheim-Roxheim</option>
        <option value="597" title="Ortsgemeinde Boos">OG Boos</option>
        <option value="505" title="Ortsgemeinde Bornich">OG Bornich</option>
        <option value="130" title="Ortsgemeinde Brachbach">OG Brachbach</option>
        <option value="726" title="Ortsgemeinde Braunshorn">OG Braunshorn</option>
        <option value="410" title="Ortsgemeinde Breitenau">OG Breitenau</option>
        <option value="619" title="Ortsgemeinde Breitscheid">OG Breitscheid</option>
        <option value="386" title="Ortsgemeinde Breitscheidt">OG Breitscheidt</option>
        <option value="525" title="Ortsgemeinde Bremberg">OG Bremberg</option>
        <option value="710" title="Ortsgemeinde Bremm">OG Bremm</option>
        <option value="222" title="Ortsgemeinde Bretthausen">OG Bretthausen </option>
        <option value="628" title="Ortsgemeinde Brey">OG Brey</option>
        <option value="555" title="Ortsgemeinde Briedern">OG Briedern</option>
        <option value="279" title="Ortsgemeinde Brockscheid">OG Brockscheid</option>
        <option value="387" title="Ortsgemeinde Bruchertseifen">OG Bruchertseifen</option>
        <option value="711" title="Ortsgemeinde Bruttig-Fankel">OG Bruttig-Fankel</option>
        <option value="727" title="Ortsgemeinde Buch">OG Buch</option>
        <option value="542" title="Ortsgemeinde Burgschwallbach">OG Burgschwallbach</option>
        <option value="638" title="Ortsgemeinde Busenhausen">OG Busenhausen</option>
        <option value="785" title="Ortgemeinde Bärenbach (Hunsrück)">OG Bärenbach (Hunsrück)</option>
        <option value="176" title="Ortsgemeinde Böchingen">OG Böchingen</option>
        <option value="144" title="Gemeinde Böhl-Iggelheim (07338005)">OG Böhl-Iggelheim</option>
        <option value="953" title="Ortsgemeinde Bölsberg">OG Bölsberg</option>
        <option value="787" title="Ortsgemeinde Büchenbeuren">OG Büchenbeuren</option>
        <option value="411" title="Ortsgemeinde Caan">OG Caan</option>
        <option value="377" title="Ortsgemeinde Charlottenberg">OG Charlottenberg</option>
        <option value="364" title="Ortsgemeinde Cramberg">OG Cramberg</option>
        <option value="398" title="Ortsgemeinde Daaden">OG Daaden</option>
        <option value="506" title="Ortsgemeinde Dahlheim">OG Dahlheim</option>
        <option value="972" title="Ortsgemeinde Dahnen">OG Dahnen</option>
        <option value="973" title="Ortsgemeinde Daleiden">OG Daleiden</option>
        <option value="280" title="Ortsgemeinde Darscheid">OG Darscheid</option>
        <option value="974" title="Ortgemeinde Dasburg">OG Dasburg</option>
        <option value="620" title="Ortsgemeinde Datzeroth">OG Datzeroth</option>
        <option value="474" title="Ortsgemeinde Dausenau ">OG Dausenau </option>
        <option value="412" title="Ortsgemeinde Deesen">OG Deesen</option>
        <option value="282" title="Ortsgemeinde Demerath">OG Demerath</option>
        <option value="456" title="Ortsgemeinde Densborn">OG Densborn</option>
        <option value="996" title="Ortsgemeinde Dernau">OG Dernau</option>
        <option value="111" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Dernbach</option>
        <option value="399" title="Ortsgemeinde Derschen">OG Derschen</option>
        <option value="481" title="Ortsgemeinde Dessighofen">OG Dessighofen</option>
        <option value="283" title="Ortsgemeinde Deudesfeld">OG Deudesfeld</option>
        <option value="750" title="Ortsgemeinde Dichtelbach">OG Dichtelbach</option>
        <option value="156" title="Ortsgemeinde Dickendorf (07132020)">OG Dickendorf</option>
        <option value="482" title="Ortsgemeinde Dienethal">OG Dienethal</option>
        <option value="789" title="Ortsgemeinde Dill">OG Dill</option>
        <option value="790" title="Ortsgemeinde Dillendorf">OG Dillendorf</option>
        <option value="598" title="Ortsgemeinde Ditscheid">OG Ditscheid</option>
        <option value="284" title="Ortsgemeinde Dockweiler">OG Dockweiler</option>
        <option value="712" title="Ortsgemeinde Dohr">OG Dohr</option>
        <option value="728" title="Ortsgemeinde Dommershausen">OG Dommershausen</option>
        <option value="483" title="Ortsgemeinde Dornholzhausen">OG Dornholzhausen</option>
        <option value="327" title="Ortsgemeinde Dreikirchen">OG Dreikirchen</option>
        <option value="285" title="Ortsgemeinde Dreis-Brück">OG Dreis-Brück</option>
        <option value="954" title="Ortsgemeinde Dreisbach">OG Dreisbach</option>
        <option value="575" title="Ortsgemeinde Dudenhofen">OG Dudenhofen</option>
        <option value="457" title="Ortsgemeinde Duppach">OG Duppach</option>
        <option value="370" title="Ortsgemeinde Dörnberg">OG Dörnberg</option>
        <option value="507" title="Ortsgemeinde Dörscheid">OG Dörscheid</option>
        <option value="526" title="Ortsgemeinde Dörsdorf">OG Dörsdorf</option>
        <option value="527" title="Ortsgemeinde Ebertshausen">OG Ebertshausen</option>
        <option value="713" title="Ortsgemeinde Ediger-Eller">OG Ediger-Eller</option>
        <option value="639" title="Ortsgemeinde Eichelhardt">OG Eichelhardt</option>
        <option value="246" title="Ortsgemeinde Einig">OG Einig</option>
        <option value="528" title="Ortsgemeinde Eisighofen">OG Eisighofen</option>
        <option value="154" title="Ortsgemeinde Elben (07132024)">OG Elben</option>
        <option value="328" title="Ortsgemeinde Elbingen">OG Elbingen</option>
        <option value="152" title="Ortsgemeinde Elkenroth (07132025)">OG Elkenroth</option>
        <option value="556" title="Ortsgemeinde Ellenz-Poltersdorf">OG Ellenz-Poltersdorf</option>
        <option value="751" title="Ortsgemeinde Ellern (Hunsrück)">OG Ellern (Hunsrück)</option>
        <option value="286" title="Ortsgemeinde Ellscheid">OG Ellscheid</option>
        <option value="223" title="Ortsgemeinde Elsoff (Westerwald)">OG Elsoff (Westerwald)</option>
        <option value="400" title="Ortsgemeinde Emmerzhausen">OG Emmerzhausen</option>
        <option value="362" title="Ortsgemeinde Eppenrod">OG Eppenrod</option>
        <option value="752" title="Ortsgemeinde Erbach (Hunsrück)">OG Erbach (Hunsrück)</option>
        <option value="529" title="Ortsgemeinde Ergeshausen">OG Ergeshausen</option>
        <option value="714" title="Ortsgemeinde Ernst">OG Ernst</option>
        <option value="640" title="Ortsgemeinde Ersfeld">OG Ersfeld</option>
        <option value="264" title="Ortsgemeinde Esch">OG Esch</option>
        <option value="177" title="Ortsgemeinde Eschbach">OG Eschbach</option>
        <option value="329" title="Ortsgemeinde Ettinghausen">OG Ettinghausen</option>
        <option value="599" title="Ortsgemeinde Ettringen">OG Ettringen</option>
        <option value="388" title="Ortsgemeinde Etzbach">OG Etzbach</option>
        <option value="113" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Eusserthal</option>
        <option value="475" title="Ortsgemeinde Fachbach">OG Fachbach</option>
        <option value="715" title="Ortsgemeinde Faid">OG Faid</option>
        <option value="955" title="Ortsgemeinde Fehl-Ritzhausen">OG Fehl-Ritzhausen</option>
        <option value="151" title="Ortsgemeinde Fensdorf (07132030)">OG Fensdorf</option>
        <option value="265" title="Ortsgemeinde Feusdorf">OG Feusdorf</option>
        <option value="641" title="Ortsgemeinde Fiersbach">OG Fiersbach</option>
        <option value="543" title="Ortsgemeinde Flacht">OG Flacht</option>
        <option value="642" title="Ortsgemeinde Fluterschen">OG Fluterschen</option>
        <option value="643" title="Ortsgemeinde Forstmehren">OG Forstmehren</option>
        <option value="178" title="Ortsgemeinde Frankweiler">OG Frankweiler</option>
        <option value="401" title="Ortsgemeinde Friedewald">OG Friedewald</option>
        <option value="133" title="Ortsgemeinde Friesenhagen">OG Friesenhagen</option>
        <option value="476" title="Ortsgemeinde Frücht">OG Frücht</option>
        <option value="586" title="Ortsgemeinde Fußgönheim">OG Fussgönheim</option>
        <option value="390" title="Ortsgemeinde Fürthen">OG Fürthen</option>
        <option value="247" title="Ortsgemeinde Gappenach">OG Gappenach</option>
        <option value="150" title="Ortsgemeinde Gebhardshain (07132039)">OG Gebhardshain</option>
        <option value="287" title="Ortsgemeinde Gefell">OG Gefell</option>
        <option value="791" title="Ortsgemeinde Gehlweiler">OG Gehlweiler</option>
        <option value="365" title="Ortsgemeinde Geilnau">OG Geilnau</option>
        <option value="484" title="Ortsgemeinde Geisig">OG Geisig</option>
        <option value="792" title="Ortsgemeinde Gemünden">OG Gemünden</option>
        <option value="248" title="Ortsgemeinde Gering">OG Gering</option>
        <option value="644" title="Ortsgemeinde Gieleroth">OG Gieleroth</option>
        <option value="249" title="Ortsgemeinde Gierschnach">OG Gierschnach</option>
        <option value="288" title="Ortsgemeinde Gillenfeld">OG Gillenfeld</option>
        <option value="114" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Gossweiler-Stein</option>
        <option value="716" title="Ortsgemeinde Greimersburg">OG Greimersburg</option>
        <option value="876" title="Ortsgemeinde Großmaischeid">OG Großmaischeid</option>
        <option value="580" title="Ortsgemeinde Großniedesheim">OG Großniedesheim</option>
        <option value="956" title="Ortsgemeinde Großseifen">OG Großseifen</option>
        <option value="530" title="Ortsgemeinde Gutenacker">OG Gutenacker</option>
        <option value="179" title="Ortgemeinde Göcklingen">OG Göcklingen</option>
        <option value="729" title="Ortsgemeinde Gödenroth">OG Gödenroth</option>
        <option value="266" title="Ortsgemeinde Gönnersdorf / Eifel">OG Gönnersdorf / Eifel</option>
        <option value="366" title="Ortsgemeinde Gückingen">OG Gückingen</option>
        <option value="793" title="Ortsgemeinde Hahn">OG Hahn</option>
        <option value="330" title="Ortsgemeinde Hahn am See">OG Hahn am See</option>
        <option value="957" title="Ortsgemeinde Hahn bei Marienberg">OG Hahn bei Marienberg</option>
        <option value="544" title="Ortsgemeinde Hahnstätten">OG Hahnstätten</option>
        <option value="267" title="Ortsgemeinde Hallschlag">OG Hallschlag</option>
        <option value="371" title="Ortsgemeinde Hambach">OG Hambach</option>
        <option value="391" title="Ortsgemeinde Hamm (Sieg)">OG Hamm (Sieg)</option>
        <option value="576" title="Ortsgemeinde Hanhofen">OG Hanhofen</option>
        <option value="131" title="Ortsgemeinde Harbach">OG Harbach</option>
        <option value="958" title="Ortsgemeinde Hardt">OG Hardt</option>
        <option value="577" title="Ortsgemeinde Harthausen">OG Harthausen</option>
        <option value="645" title="Ortsgemeinde Hasselbach">OG Hasselbach</option>
        <option value="730" title="Ortsgemeinde Hasselbach (Hunsrück)">OG Hasselbach (Hunsrück)</option>
        <option value="600" title="Ortsgemeinde Hausten">OG Hausten</option>
        <option value="794" title="Ortsgemeinde Hecken">OG Hecken</option>
        <option value="997" title="Ortsgemeinde Heckenbach">OG Heckenbach</option>
        <option value="795" title="Ortsgemeinde Heinzenbach">OG Heinzenbach</option>
        <option value="360" title="Ortsgemeinde Heistenbach">OG Heistenbach</option>
        <option value="224" title="Ortsgemeinde Hellenhahn-Schellenberg">OG Hellenhahn-Schellenberg</option>
        <option value="646" title="Ortsgemeinde Helmenzen">OG Helmenzen</option>
        <option value="647" title="Ortsgemeinde Helmeroth">OG Helmeroth</option>
        <option value="648" title="Ortsgemeinde Hemmelzen">OG Hemmelzen</option>
        <option value="796" title="Ortsgemeinde Henau">OG Henau</option>
        <option value="864" title="Ortsgemeinde Herforst">OG Herforst</option>
        <option value="531" title="Ortsgemeinde Herold">OG Herold</option>
        <option value="601" title="Ortsgemeinde Herresbach">OG Herresbach</option>
        <option value="331" title="Ortsgemeinde Herschbach / Oberwesterwald">OG Herschbach/Oww.</option>
        <option value="582" title="Ortsgemeinde Heuchelheim bei Frankenthal">OG Heuchelheim bei Frankenthal</option>
        <option value="180" title="Ortsgemeinde Heuchelheim-Klingen">OG Heuchelheim-Klingen</option>
        <option value="649" title="Ortsgemeinde Heupelzen">OG Heupelzen</option>
        <option value="581" title="Ortsgemeinde Heßheim">OG Heßheim</option>
        <option value="650" title="Ortsgemeinde Hilgenroth">OG Hilgenroth</option>
        <option value="289" title="Ortsgemeinde Hinterweiler">OG Hinterweiler</option>
        <option value="373" title="Ortsgemeinde Hischberg">OG Hirschberg</option>
        <option value="797" title="Ortsgemeinde Hirschfeld (Hunsrück)">OG Hirschfeld (Hunsrück)</option>
        <option value="602" title="Ortsgemeinde Hirten">OG Hirten</option>
        <option value="651" title="Ortsgemeinde Hirz-Maulsbach">OG Hirz-Maulsbach</option>
        <option value="959" title="Ortsgemeinde Hof">OG Hof</option>
        <option value="458" title="Ortsgemeinde Hohenfels-Essingen">OG Hohenfels-Essingen</option>
        <option value="731" title="Ortsgemeinde Hollnich">OG Hollnich</option>
        <option value="375" title="Ortsgemeinde Holzappel">OG Holzappel</option>
        <option value="374" title="Ortsgemeinde Holzheim">OG Holzheim</option>
        <option value="225" title="Ortsgemeinde Homberg">OG Homberg</option>
        <option value="369" title="Ortsgemeinde Horhausen">OG Horhausen</option>
        <option value="865" title="Ortsgemeinde Hosten">OG Hosten</option>
        <option value="332" title="Ortsgmeinde Hundsangen">OG Hundsangen</option>
        <option value="413" title="Ortsgemeinde Hundsdorf">OG Hundsdorf</option>
        <option value="485" title="Ortsgemeinde Hömberg">OG Hömberg</option>
        <option value="998" title="Ortsgemeinde Hönningen">OG Hönningen</option>
        <option value="218" title="Ortsgemeinde Hördt">OG Hördt</option>
        <option value="290" title="Ortsgemeinde Hörscheid">OG Hörscheid</option>
        <option value="703" title="Ortsgemeinde Hövels">OG Hövels</option>
        <option value="226" title="Ortsgemeinde Hüblingen">OG Hüblingen</option>
        <option value="652" title="Ortsgemeinde Idelberg">OG Idelberg</option>
        <option value="181" title="Ortsgemeinde Ilbesheim">OG Ilbesheim</option>
        <option value="291" title="Ortsgemeinde Immerath">OG Immerath</option>
        <option value="182" title="Ortsgemeinde Impflingen">OG Impflingen</option>
        <option value="653" title="Ortsgemeinde Ingelbach">OG Ingelbach</option>
        <option value="227" title="Ortsgemeinde Irmtraut">OG Irmtraut</option>
        <option value="975" title="Ortsgemeinde Irrhausen">OG Irrhausen</option>
        <option value="877" title="Ortsgemeinde Isenburg">OG Isenburg</option>
        <option value="654" title="Ortsgemeinde Isert">OG Isert</option>
        <option value="358" title="Ortsgemeinde Isselbach">OG Isselbach</option>
        <option value="268" title="Ortsgemeinde Jünkerath">OG Jünkerath</option>
        <option value="999" title="Ortsgemeinde Kalenborn">OG Kalenborn</option>
        <option value="459" title="Ortsgemeinde Kalenborn-Scheuern">OG Kalenborn-Scheuern</option>
        <option value="250" title="Ortsgemeinde Kalt">OG Kalt</option>
        <option value="320" title="Ortsgemeinde Kaltenengers">OG Kaltenengers</option>
        <option value="545" title="Ortsgemeinde Kaltenholzhausen">OG Kaltenholzhausen</option>
        <option value="905" title="Ortsgemeinde Kanzem">OG Kanzem</option>
        <option value="798" title="Ortsgemeinde Kappell">OG Kappel</option>
        <option value="704" title="Ortsgemeinde Katzwinkel (Sieg)">OG Katzwinkel (Sieg)</option>
        <option value="158" title="Ortsgemeinde Kausen (07132059)">OG Kausen</option>
        <option value="603" title="Ortsgemeinde Kehrig">OG Kehrig</option>
        <option value="477" title="Ortsgemeinde Kemmenau">OG Kemmenau</option>
        <option value="251" title="Ortsgemeinde Kerben">OG Kerbern</option>
        <option value="269" title="Ortsgemeinde Kerschenbach">OG Kerschenbach</option>
        <option value="976" title="Ortsgemeinde Kesfeld">OG Kesfeld</option>
        <option value="1000" title="Ortsgemeinde Kesseling">OG Kesseling</option>
        <option value="509" title="Ortsgemeinde Kestert">OG Kestert</option>
        <option value="655" title="Ortegemeinde Kettenhausen">OG Kettenhausen</option>
        <option value="319" title="Ortsgemeinde Kettig">OG Kettig</option>
        <option value="960" title="Ortsgemeinde Kirburg">OG Kirburg</option>
        <option value="656" title="Ortsgemeinde Kircheib">OG Kircheib</option>
        <option value="1001" title="Ortsgemeinde Kirchsahr">OG Kirchsahr</option>
        <option value="604" title="Ortsgemeinde Kirchwald">OG Kirchwald</option>
        <option value="292" title="Ortsgemeinde Kirchweiler">OG Kirchweiler</option>
        <option value="753" title="Ortsgemeinde Kisselbach">OG Kisselbach</option>
        <option value="878" title="Ortsgemeinde Kleinmaischeid">OG Kleinmaischeid</option>
        <option value="583" title="Ortsgemeinde Kleinniedesheim">OG Kleinniedesheim</option>
        <option value="717" title="Ortsgemeinde Klotten">OG Klotten</option>
        <option value="800" title="Ortsgemeinde Kludenbach">OG Kludenbach</option>
        <option value="183" title="Ortsgemeinde Knöringen">OG Knöringen</option>
        <option value="460" title="Ortsgemeinde Kopp">OG Kopp</option>
        <option value="733" title="Ortsgemeinde Korweiler">OG Korweiler</option>
        <option value="605" title="Ortsgemeinde Kottenheim">OG Kottenheim</option>
        <option value="657" title="Ortsgemeinde Kraam">OG Kraam</option>
        <option value="293" title="Ortsgemeind Kradenbach">OG Kradenbach</option>
        <option value="189" title="Ortsgemeinde Kretz">OG Kretz</option>
        <option value="190" title="Ortsgemeinde Kruft">OG Kruft</option>
        <option value="219" title="Ortsgemeinde Kuhardt">OG Kuhardt</option>
        <option value="333" title="Ortsgemeinde Kuhnhöfen">OG Kuhnhöfen</option>
        <option value="534" title="Ortsgemeinde Kördorf">OG Kördorf</option>
        <option value="977" title="Ortsgemeinde Lambertsberg">OG Lambertsberg</option>
        <option value="146" title="Ortsgemeinde Lambsheim">OG Lambsheim</option>
        <option value="961" title="Ortsgemeinde Langenbach bei Kirburg">OG Langenbach bei Kirburg</option>
        <option value="606" title="Ortsgemeinde Langenfeld">OG Langenfeld</option>
        <option value="372" title="Ortsgemeinde Langenscheid">OG Langenscheid</option>
        <option value="607" title="Ortsgemeinde Langscheid">OG Langscheid</option>
        <option value="801" title="Ortsgemeinde Laufersweiler">OG Laufersweiler</option>
        <option value="379" title="Ortsgemeinde Laurenburg">OG Laurenburg</option>
        <option value="962" title="Ortsgemeinde Lautzenbrücken">OG Lautzenbrücken</option>
        <option value="802" title="Ortsgemeinde Lautzenhausen">OG Lautzenhausen</option>
        <option value="220" title="Ortsgemeinde Leimersheim">OG Leimersheim</option>
        <option value="184" title="Ortsgemeinde Leinsweiler">OG Leinsweiler</option>
        <option value="978" title="Ortsgemeinde Lichtenborn">OG Lichtenborn</option>
        <option value="228" title="Ortsgemeinde Liebenscheid">OG Liebenscheid</option>
        <option value="754" title="Ortsgemeinde Liebshausen">OG Liebshausen</option>
        <option value="979" title="Ortsgemeinde Lierfeld">OG Lierfeld</option>
        <option value="510" title="Ortsgemeinde Lierschied">OG Lierschied</option>
        <option value="572" title="Ortsgemeinde Limburgerhof">OG Limburgerhof</option>
        <option value="608" title="Ortsgemeinde Lind">OG Lind</option>
        <option value="1002" title="Ortsgemeinde Lind">OG Lind (07131047)</option>
        <option value="803" title="Ortsgemeinde Lindenschied">OG Lindenschied</option>
        <option value="270" title="Ortsgemeinde Lissendorf">OG Lissendorf</option>
        <option value="546" title="Ortsgemeinde Lohrheim">OG Lohrheim</option>
        <option value="486" title="Ortsgemeinde Lollschied">OG Lollschied</option>
        <option value="252" title="Ortsgemeinde Lonnig">OG Lonnig</option>
        <option value="609" title="Ortsgemeinde Luxem">OG Luxem</option>
        <option value="511" title="Ortsgemeinde Lykershausen">OG Lykershausen</option>
        <option value="980" title="Ortsgemeinde Lünebach">OG Lünebach</option>
        <option value="804" title="Ortsgemeinde Maitzborn">OG Maitzborn</option>
        <option value="149" title="Ortsgemeinde Malberg (07132066)">OG Malberg</option>
        <option value="658" title="Ortsgemeinde Mammelzen">OG Mammelzen</option>
        <option value="981" title="Ortsgemeinde Manderscheid">OG Manderscheid (07232264)</option>
        <option value="879" title="Ortsgemeinde Marienhausen">OG Marienhausen</option>
        <option value="734" title="Ortsgemeinde Mastershausen">OG Mastershausen</option>
        <option value="402" title="Ortsgemeinde Mauden">OG Mauden</option>
        <option value="587" title="Ortsgemeinde Maxdorf">OG Maxdorf</option>
        <option value="1003" title="Ortsgemeinde Mayschoß">OG Mayschoß</option>
        <option value="294" title="Ortsgemeinde Mehren">OG Mehren</option>
        <option value="659" title="Ortsgemeinde Mehren">OG Mehren (Ww.)</option>
        <option value="295" title="Ortsgemeinde Meisburg">OG Meisburg</option>
        <option value="253" title="Ortsgemeinde Mertloch">OG Mertloch</option>
        <option value="718" title="Ortsgemeinde Mesenich">OG Mesenich</option>
        <option value="805" title="Ortsgemeinde Metzenhausen">OG Metzenhausen</option>
        <option value="335" title="Ortsgemeinde Meudt">OG Meudt</option>
        <option value="660" title="Ortsgemeinde Michelbach">OG Michelbach</option>
        <option value="735" title="Ortsgemeinde Michelbach (Husrück)">OG Michelbach (Husrück)</option>
        <option value="478" title="Ortsgemeinde Miellen">OG Miellen</option>
        <option value="487" title="Ortsgemeinde Misselberg">OG Misselberg</option>
        <option value="535" title="Ortsgemeinde Mittelfischbach">OG Mittelfischbach</option>
        <option value="705" title="Ortsgemeinde Mittelhof">OG Mittelhof</option>
        <option value="336" title="Ortsgemeinde Molsberg">OG Molsberg</option>
        <option value="155" title="Ortsgemeinde Molzhain (07132071)">OG Molzhain</option>
        <option value="610" title="Ortsgemeinde Monreal">OG Monreal</option>
        <option value="132" title="Ortsgemeinde Mudersbach">OG Mudersbach</option>
        <option value="547" title="Ortsgemeinde Mudershausen">OG Mudershausen</option>
        <option value="573" title="Ortsgemeinde Mutterstadt">OG Mutterstadt</option>
        <option value="334" title="Ortsgemeinde Mähren">OG Mähren</option>
        <option value="963" title="Ortsgemeinde Mörlen">OG Mörlen</option>
        <option value="755" title="Ortsgemeinde Mörschbach">OG Mörschbach</option>
        <option value="296" title="Ortsgemeinde Mückeln">OG Mückeln</option>
        <option value="116" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Münchweiler am Klingbach</option>
        <option value="611" title="Ortsgemeinde Münk">OG Münk</option>
        <option value="461" title="Ortsgemeinde Mürlenbach">OG Mürlenbach</option>
        <option value="612" title="Ortsgemeinde Nachtsheim">OG Nachtsheim</option>
        <option value="255" title="Ortsgemeinde Naunheim">OG Naunheim</option>
        <option value="414" title="Ortsgemeinde Nauort">OG Nauort</option>
        <option value="157" title="Ortsgemeinde Nauroth (07132073)">OG Nauroth</option>
        <option value="557" title="Ortsgemeinde Nehren">OG Nehren</option>
        <option value="661" title="Ortsgemeinde Neitersen">OG Neitersen</option>
        <option value="297" title="Ortsgemeinde Nerdlen">OG Nerdlen</option>
        <option value="462" title="Ortsgemeinde Neroth">OG Neroth</option>
        <option value="548" title="Ortsgemeinde Netzbach">OG Netzbach</option>
        <option value="569" title="Ortsgemeinde Neuhofen">OG Neuhofen</option>
        <option value="964" title="Ortsgemeinde Neunkhausen">OG Neunkhausen</option>
        <option value="229" title="Ortsgemeinde Niederkirchen">OG Neunkirchen</option>
        <option value="230" title="Ortsgemeinde Neustadt (Westerwald)">OG Neustadt (Westerwald)</option>
        <option value="191" title="Ortsgemeinde Nickenich">OG Nickenich</option>
        <option value="806" title="Ortsgemeinde Nieder Kostenz">OG Nieder Kostenz</option>
        <option value="337" title="Ortsgemeinde Niederahr">OG Niederahr</option>
        <option value="622" title="Ortsgemeinde Niederbreitbach">OG Niederbreitbach</option>
        <option value="403" title="Ortsgemeinde Niederdreisbach">OG Niederdreisbach</option>
        <option value="128" title="Ortsgemeinde Niederfischbach">OG Niederfischbach</option>
        <option value="392" title="Ortsgemeinde Niederirsen">OG Niederirsen</option>
        <option value="549" title="Ortsgemeinde Niederneisen">OG Niederneisen</option>
        <option value="231" title="Ortsgemeinde Niederroßbach">OG Niederroßbach</option>
        <option value="807" title="Ortsgemeinde Niedersohren">OG Niedersohren</option>
        <option value="298" title="Ortsgemeinde Niederstadtfeld">OG Niederstadtfeld</option>
        <option value="536" title="Ortsgemeinde Niedertiefenbach">OG Niedertiefenbach</option>
        <option value="808" title="Ortsgemeinde Niederweiler (Hunsrück">OG Niederweiler (Hunsrück)</option>
        <option value="348" title="Ortsgemeinde Niederwerth">OG Niederwerth</option>
        <option value="479" title="Ortsgemeinde Nievern">OG Nievern</option>
        <option value="232" title="Ortsgemeinde Nister-Möhrendorf">OG Nister-Möhrendorf</option>
        <option value="965" title="Ortsgemeinde Nisterau">OG Nisterau</option>
        <option value="404" title="Ortsgemeinde Nisterberg">OG Nisterberg</option>
        <option value="966" title="Ortsgemeinde Nistertal">OG Nistertal</option>
        <option value="907" title="Ortsgemeinde Nittel">OG Nittel</option>
        <option value="512" title="Ortsgemeinde Nochern">OG Nochern</option>
        <option value="967" title="Ortsgemeinde Norken">OG Norken</option>
        <option value="809" title="Ortsgemeinde Ober Kostenz">OG Ober Kostenz</option>
        <option value="338" title="Ortsgemeinde Oberahr">OG Oberahr</option>
        <option value="908" title="Ortsgemeinde Oberbillig">OG Oberbillig</option>
        <option value="339" title="Ortsgemeinde Obererbach">OG Obererbach</option>
        <option value="662" title="Ortsgemeinde Obererbach">OG Obererbach (Ak.)</option>
        <option value="537" title="Ortsgemeinde Oberfischbach">OG Oberfischbach</option>
        <option value="415" title="Ortsgemeinde Oberhaid">OG Oberhaid</option>
        <option value="663" title="Ortsgemeinde Oberirsen">OG Oberirsen</option>
        <option value="550" title="Ortsgemeinde Oberneisen">OG Oberneisen</option>
        <option value="489" title="Ortsgemeinde Obernhof">OG Obernhof</option>
        <option value="982" title="Ortgemeinde Oberpierscheid">OG Oberpierscheid</option>
        <option value="233" title="Ortsgemeinde Oberrod">OG Oberrod</option>
        <option value="234" title="Ortsgemeinde Oberroßbach">OG Oberroßbach</option>
        <option value="299" title="Ortsgemeinde Oberstadtfeld">OG Oberstadtfeld</option>
        <option value="664" title="Ortsgemeinde Oberwambach">OG Oberwambach</option>
        <option value="490" title="Ortsgemeinde Oberwies">OG Oberwies</option>
        <option value="256" title="Ortsgemeinde Ochtendung">OG Ochtendung</option>
        <option value="909" title="Ortsgemeinde Onsdorf">OG Onsdorf</option>
        <option value="866" title="Ortsgemeinde Orenhofen">OG Orenhofen</option>
        <option value="271" title="Ortsgemeinde Ormont">OG Ormont</option>
        <option value="162" title="Ortsgemeinde Otterstadt">OG Otterstadt</option>
        <option value="513" title="Ortsgemeinde Patersberg">OG Patersberg</option>
        <option value="910" title="Ortsgemeinde Pellingen">OG Pellingen</option>
        <option value="463" title="Ortsgemeinde Pelm">OG Pelm</option>
        <option value="258" title="Ortsgemeinde Pillig">OG Pillig</option>
        <option value="192" title="Ortsgemeinde Plaidt">OG Plaidt</option>
        <option value="983" title="Ortgemeinde Plütscheid">OG Plütscheid</option>
        <option value="491" title="Ortsgemeinde Pohl">OG Pohl</option>
        <option value="393" title="Ortsgemeinde Pracht">OG Pracht</option>
        <option value="514" title="Ortsgemeinde Prath">OG Prath</option>
        <option value="867" title="Ortsgemeinde Preist">OG Preist</option>
        <option value="666" title="Ortsgemeinde Racksen">OG Racksen</option>
        <option value="117" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Ramberg</option>
        <option value="185" title="Ortsgemeinde Ranschbach">OG Ranschbach</option>
        <option value="810" title="Ortsgemeinde Raversbeuren">OG Raversbeuren</option>
        <option value="1004" title="Ortsgemeinde Rech">OG Rech</option>
        <option value="538" title="Ortsgemeinde Reckenroth">OG Reckenroth</option>
        <option value="811" title="Ortsgemeinde Reckershausen">OG Reckershausen</option>
        <option value="235" title="Ortsgemeinde Rehe">OG Rehe</option>
        <option value="515" title="Ortsgemeinde Reichenberg">OG Reichenberg</option>
        <option value="516" title="Ortsgemeinde Reitzenhain">OG Reitzenhain</option>
        <option value="667" title="Ortsgemeinde Rettersen">OG Rettersen</option>
        <option value="539" title="Ortsgemeinde Rettert">OG Rettert</option>
        <option value="613" title="Ortsgemeinde Reudelsterz">OG Reudelsterz</option>
        <option value="272" title="Ortsgemeinde Reuth">OG Reuth</option>
        <option value="353" title="Ortsgemeind Rieden">OG Rieden</option>
        <option value="757" title="Ortsgemeinde Riesweiler">OG Riesweiler</option>
        <option value="118" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Rinnthal</option>
        <option value="464" title="Ortsgemeinde Rockeskyll">OG Rockeskyll</option>
        <option value="814" title="Ortsgemeinde Rohrbach (Hunsrück)">OG Rohrbach  (Hunsrück)</option>
        <option value="159" title="Ortsgemeinde Rosenheim (07132095)">OG Rosenheim</option>
        <option value="540" title="Ortsgemeinde Roth">OG Roth</option>
        <option value="736" title="Ortsgemeinde Roth (Hunsrück)">OG Roth (Hunsrück)</option>
        <option value="394" title="Ortsgemeinde Roth (Ww.)">OG Roth (Ww.)</option>
        <option value="623" title="Ortsgemeinde Roßbach">OG Roßbach</option>
        <option value="812" title="Ortsgemeinde Rödelhausen">OG Rödelhausen</option>
        <option value="813" title="Ortsgemeinde Rödern">OG Rödern</option>
        <option value="145" title="Ortsgemeinde Römerberg">OG Römerberg</option>
        <option value="259" title="Ortsgemeinde Rüber">OG Rüber</option>
        <option value="221" title="Ortsgemeinde Rülzheim">OG Rülzheim</option>
        <option value="193" title="Ortsgemeinde Saffig">OG Saffig</option>
        <option value="465" title="Ortsgemeinde Salm">OG Salm</option>
        <option value="340" title="Ortsgemeinde Salz">OG Salz</option>
        <option value="237" title="Ortsgemeinde Salzburg">OG Salzburg</option>
        <option value="300" title="Ortsgemeinde Samersbach">OG Samersbach</option>
        <option value="614" title="Ortsgemeinde Sankt Johann">OG Sankt Johann</option>
        <option value="518" title="Ortsgemeinde Sauerthal">OG Sauerthal</option>
        <option value="301" title="Ortsgemeinde Saxler">OG Saxler</option>
        <option value="302" title="Ortsgemeinde Schalkenmehren">OG Schalkenmehren</option>
        <option value="273" title="Ortsgemeinde Scheid">OG Scheid</option>
        <option value="368" title="Ortsgemeinde Scheidt">OG Scheidt</option>
        <option value="551" title="Ortsgemeinde Schiesheim">OG Schiesheim</option>
        <option value="815" title="Ortsgemeinde Schlierschied">OG Schlierschied</option>
        <option value="758" title="Ortsgemeinde Schnorbach">OG Schnorbach</option>
        <option value="304" title="Ortsgemeinde Schutz">OG Schutz</option>
        <option value="405" title="Ortsgemeinde Schutzbach">OG Schutzbach</option>
        <option value="816" title="Ortsgemeinde Schwarzen">OG Schwarzen</option>
        <option value="492" title="Ortsgemeinde Schweighausen">OG Schweighausen</option>
        <option value="303" title="Ortsgemeinde Schönbach">OG Schönbach</option>
        <option value="541" title="Ortsgemeinde Schönborn">OG Schönborn</option>
        <option value="668" title="Ortsgemeinde Schöneberg">OG Schöneberg</option>
        <option value="274" title="Ortsgemeinde Schüller">OG Schüller</option>
        <option value="238" title="Ortsgemeinde Seck">OG Seck</option>
        <option value="493" title="Ortsgemeinde Seelbach (Nassau)">OG Seelbach (Nassau)</option>
        <option value="395" title="Ortsgemeinde Seelbach bei Hamm">OG Seelbach bei Hamm</option>
        <option value="706" title="Ortsgemeinde Selbach (Sieg)">OG Selbach (Sieg)</option>
        <option value="719" title="Ortsgemeinde Senheim">OG Senheim</option>
        <option value="416" title="Ortsgemeinde Sessenbach">OG Sessenbach</option>
        <option value="186" title="Ortsgemeinde Siebeldingen">OG Siebeldingen</option>
        <option value="615" title="Ortsgemeinde Siebenbach">OG Siebenbach</option>
        <option value="119" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Silz</option>
        <option value="494" title="Ortsgemeinde Singhofen">OG Singhofen</option>
        <option value="817" title="Ortsgemeinde Sohren">OG Sohren</option>
        <option value="818" title="Ortsgemeinde Sohrschied">OG Sohrschied</option>
        <option value="868" title="Ortsgemeinde Spangdahlem">OG Spangdahlem</option>
        <option value="629" title="Ortsgemeinde Spay">OG Spay</option>
        <option value="737" title="Ortsgemeinde Spesenroth">OG Spesenroth</option>
        <option value="317" title="Ortsgemeinde St.Sebastian">OG St.Sebastian</option>
        <option value="275" title="Ortsgemeinde Stadtkyll">OG Stadtkyll</option>
        <option value="880" title="Ortsgemeide Stebach">OG Stebach</option>
        <option value="276" title="Ortsgemeinde Steffeln">OG Steffeln</option>
        <option value="239" title="Ortsgemeinde Stein-Neukirch">OG Stein-Neukirch</option>
        <option value="759" title="Ortsgemeinde Steinbach (Hunsrück)">OG Steinbach (Husrück)</option>
        <option value="143" title="Ortsgemeinde Steinebach (Sieg) (07132107)">OG Steinebach (Sieg)</option>
        <option value="305" title="Ortsgemeinde Steineberg">OG Steineberg</option>
        <option value="341" title="Ortsgemeinde Steinefrenz">OG Steinefrenz</option>
        <option value="153" title="Ortsgemeinde Steineroth (07132108)">OG Steineroth</option>
        <option value="306" title="Ortsgemeinde Steiningen">OG Steiningen</option>
        <option value="367" title="Ortsgemeinde Steinsberg">OG Steinsberg</option>
        <option value="968" title="Ortsgemeinde Storken-Illfurth">OG Storken-Illfurth</option>
        <option value="307" title="Ortsgemeinde Strohn">OG Strohn</option>
        <option value="308" title="Ortsgemende Strotzbüsch">OG Strotzbüsch</option>
        <option value="670" title="Ortsgemeinde Stürzelbach">OG Stürzelbach</option>
        <option value="495" title="Ortsgemeinde Sulzbach">OG Sulzbach</option>
        <option value="669" title="Ortsgemeinde Sörth">OG Sörth</option>
        <option value="911" title="Ortsgemeinde Tawern">OG Tawern</option>
        <option value="912" title="Ortsgemeinde Temmels">OG Temmels</option>
        <option value="355" title="Ortsgemeinde Thür">OG Thür</option>
        <option value="819" title="Ortsgemeinde Todenroth">OG Todenroth</option>
        <option value="260" title="Ortsgemeinde Trimbs">OG Trimbs</option>
        <option value="309" title="Ortsgemeinde Udler">OG Udler</option>
        <option value="738" title="Ortsgemeinde Uhler">OG Uhler</option>
        <option value="969" title="Ortsgemeinde Unnau">OG Unnau</option>
        <option value="349" title="Ortsgemeinde Urbar">OG Urbar</option>
        <option value="316" title="Ortsgemeinde Urmitz">OG Urmitz</option>
        <option value="311" title="Ortsgemeinde Utzerath">OG Utzerath</option>
        <option value="616" title="Ortsgemeinde Virneburg">OG Virneburg</option>
        <option value="120" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Voelkersweiler</option>
        <option value="671" title="Ortsgemeinde Volkerzen">OG Volkerzen</option>
        <option value="357" title="Ortsgemeinde Volkesfeld">OG Volkesfeld</option>
        <option value="821" title="Ortsgemeinde Wahlenau">OG Wahlenau</option>
        <option value="240" title="Ortsgemeinde Waigandshain">OG Waigandshain</option>
        <option value="624" title="Ortsgemeinde Waldbreitbach">OG Waldbreitbach</option>
        <option value="626" title="Ortsgemeinde Waldesch">OG Waldesch</option>
        <option value="121" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Waldhambach</option>
        <option value="241" title="Ortsgemeinde Waldmühlen">OG Waldmühlen</option>
        <option value="122" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Waldrohrbach</option>
        <option value="161" title="Ortsgemeinde Waldsee">OG Waldsee</option>
        <option value="312" title="Ortsgemeinde Wallenborn">OG Wallenborn</option>
        <option value="342" title="Ortsgemeinde Wallmerod">OG Wallmerod</option>
        <option value="187" title="Ortsgemeinde Walsheim">OG Walsheim</option>
        <option value="376" title="Ortsgemeinde Wasenbach">OG Wasenbach</option>
        <option value="913" title="Ortsgemeinde Wasserliesch">OG Wasserliesch</option>
        <option value="914" title="Ortsgemeinde Wawern">OG Wawern</option>
        <option value="985" title="Ortsgemeinde Waxweiler">OG Waxweiler</option>
        <option value="313" title="Ortsgemeinde Weidenbach">OG Weidenbach</option>
        <option value="617" title="Ortsgemeinde Weiler">OG Weiler</option>
        <option value="496" title="Ortsgemeinde Weinähr">OG Weinähr</option>
        <option value="519" title="Ortsgemeinde Weisel">OG Weisel</option>
        <option value="406" title="Ortsgemeinde Weitefeld">OG Weitefeld</option>
        <option value="350" title="Ortsgemeinde Weitersburg">OG Weitersburg</option>
        <option value="915" title="Ortsgemeinde Wellen">OG Wellen</option>
        <option value="261" title="Ortsgemeinde Welling">OG Welling</option>
        <option value="618" title="Ortsgemeinde Welchenbach">OG Welschenbach</option>
        <option value="672" title="Ortsgemeinde Werkhausen">OG Werkhausen</option>
        <option value="123" title="Es wurde noch kein Titel für die Gruppe eingestellt!">OG Wernersberg</option>
        <option value="343" title="Ortsgemeinde Weroth">OG Weroth</option>
        <option value="242" title="Ortsgemeinde Westernohe">OG Westernohe</option>
        <option value="520" title="Ortsgemeinde Weyer">OG Weyer</option>
        <option value="673" title="Ortsgemeinde Weyerbusch">OG Weyerbusch</option>
        <option value="262" title="Ortsgemeinde Wierschem">OG Wierschem</option>
        <option value="243" title="Ortsgemeinde Willingen">OG Willingen</option>
        <option value="916" title="Ortsgemeinde Wiltingen">OG Wiltingen</option>
        <option value="497" title="Ortsgemeinde Winden">OG Winden</option>
        <option value="314" title="Ortsgemeinde Winkel">OG Winkel</option>
        <option value="721" title="Ortsgemeinde Wirfus">OG Wirfus</option>
        <option value="417" title="Ortsgemeinde Wirscheid">OG Wirscheid</option>
        <option value="418" title="Ortsgemeinde Wittgert">OG Wittgert</option>
        <option value="822" title="Ortsgemeinde Womrath">OG Womrath</option>
        <option value="823" title="Ortsgemeinde Woppenroth">OG Woppenroth</option>
        <option value="674" title="Ortsgemeinde Wölmersen">OG Wölmersen</option>
        <option value="824" title="Ortsgemeinde Würrich">OG Würrich</option>
        <option value="244" title="Ortsgemeinde Zehnhausen bei Rennerod">OG Zehnhausen bei Rennerod</option>
        <option value="344" title="Ortsgemeinde Zehnhausen bei Wallmerod">OG Zehnhausen bei Wallmerod</option>
        <option value="498" title="Ortsgemeinde Zimmerschied">OG Zimmerschied</option>
        <option value="665" title="Ortsgemeinde Ölsen">OG Ölsen</option>
        <option value="310" title="Ortsgemeinde Üdersdorf">OG Üdersdorf</option>
        <option value="984" title="Ortsgemeinde Üttfeld">OG Üttfeld</option>
        <option value="949" title="SGD Süd Raumordnung Technisches Büro">SGD Süd Raumordnung Technisches Büro</option>
        <option value="29" title="Struktur- und Genehmigungsdirektion Nord">SGD-Nord - AG GIS</option>
        <option value="58" title="Es wurde noch kein Titel für die Gruppe eingestellt!">SGD-Nord - Wasserhaushalt/Gewässerögologie</option>
        <option value="630" title="Struktur- und Genehmigungsdirektion Süd - Abteilung 4 - Raumordnung, Naturschutz, Bauwesen">SGD-Süd - Raumordnung</option>
        <option value="634" title="Stadt Altenkirchen">ST Altenkirchen</option>
        <option value="589" title="Stadt Andernach">ST Andernach</option>
        <option value="884" title="Stadt Annweiler am Trifels">ST Annweiler am Trifels</option>
        <option value="471" title="Stadt Bad Ems">ST Bad Ems</option>
        <option value="952" title="Stadt Bad MArienberg (Westerwald)">ST Bad Marienberg</option>
        <option value="419" title="Stadt Bad Neuenahr - Ahrweiler">ST Bad Neuenahr - Ahrweiler</option>
        <option value="590" title="Stadt Bendorf">ST Bendorf</option>
        <option value="739" title="Stadt Boppard">ST Boppard</option>
        <option value="709" title="Stadt Cochem">ST Cochem</option>
        <option value="281" title="Stadt Daun">ST Daun</option>
        <option value="875" title="Stadt Dierdorf">ST Dierdorf</option>
        <option value="361" title="Stadt Diez">ST Diez</option>
        <option value="466" title="Stadt Gerolstein">ST Gerolstein</option>
        <option value="732" title="Stadt Kastellaun">ST Kastellaun</option>
        <option value="532" title="Stadt Katzenelnbogen">ST Katzenelnbogen</option>
        <option value="508" title="Stadt Kaub">ST Kaub</option>
        <option value="799" title="Stadt Kirchberg">ST Kirchberg</option>
        <option value="129" title="Stadt Kirchen">ST Kirchen</option>
        <option value="906" title="Stadt Konz">ST Konz</option>
        <option value="467" title="Stadt Lahnstein">ST Lahnstein</option>
        <option value="351" title="Stadt Mayen">ST Mayen</option>
        <option value="354" title="Stadt Mendig">ST Mendig</option>
        <option value="318" title="Stadt Mülheim-Kärlich">ST Mülheim-Kärlich</option>
        <option value="488" title="Stadt Nassau">ST Nassau</option>
        <option value="563" title="Stadt Pirmasens">ST Pirmasens</option>
        <option value="408" title="Stadt Rannsbach-Baumbach">ST Ransbach-Baumbach</option>
        <option value="236" title="Stadt Rennerod">ST Rennerod</option>
        <option value="756" title="Stadt Rheinböllen">ST Rheinböllen</option>
        <option value="627" title="Stadt Rhens">ST Rhens</option>
        <option value="163" title="Stadt Schifferstadt">ST Schifferstadt</option>
        <option value="869" title="Stadt Speicher">ST Speicher</option>
        <option value="517" title="Stadt Sankt Goarshausen">ST St.Goarshausen</option>
        <option value="347" title="Stadt Vallendar">ST Vallendar</option>
        <option value="315" title="Stadt Weißenthurm">ST Weißenthurm</option>
        <option value="707" title="Stadt Wissen">ST Wissen</option>
        <option value="990" title="Stadt Wittlich">ST Wittlich</option>
        <option value="381" title="Stadt Zweibrücken">ST Zweibrücken</option>
        <option value="124" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Stadt Annweiler am Trifels</option>
        <option value="77" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Stadt Bad Neuenahr-Ahrweiler</option>
        <option value="1005" title="Stadt Bielefeld - Demo">Stadt Bielefeld - Demo</option>
        <option value="164" title="Stadt Frankenthal">Stadt Frankenthal</option>
        <option value="92" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Stadt Kaiserslautern</option>
        <option value="73" title="Stadt Landau - Stadtbauamt">Stadt Landau</option>
        <option value="96" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Stadt Ludwigshafen</option>
        <option value="33" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Stadt Mainz</option>
        <option value="903" title="Stadt Worms">Stadt Worms</option>
        <option value="631" title="Statistisches Landesamt Rheinland-Pfalz ">StaLa - RLP</option>
        <option value="64" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Verbandsgemeinde Neuerburg</option>
        <option value="56" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Verbandsgemeinde Schweich</option>
        <option value="81" title="Es wurde noch kein Titel für die Gruppe eingestellt!">Verkehrsverbund Rhein-Mosel</option>
        <option value="992" title="Verbandsgemeinde Altenahr">VG Altenahr</option>
        <option value="632" title="Verbandsgemeinde Altenkirchen">VG Altenkirchen</option>
        <option value="562" title="VG Annweiler">VG Annweiler</option>
        <option value="678" title="Verbandgemeinde Arzfeld">VG Arzfeld</option>
        <option value="470" title="Verbandsgemeinde Bad Ems">VG Bad Ems</option>
        <option value="951" title="Verbandsgemeinde Bad Marienberg">VG Bad Marienberg</option>
        <option value="986" title="Verbandsgemeinde Bernkastel Kues">VG Bernkastel Kues</option>
        <option value="553" title="Verbandsgemeinde Cochem">VG Cochem</option>
        <option value="198" title="Verbandsgemeinde Daaden">VG Daaden</option>
        <option value="677" title="Verbandsgemeinde Dahner Felsenland">VG Dahner Felsenland</option>
        <option value="564" title="Verbandsgemeinde Dannstadt-Schauernheim">VG Dannstadt-Schauernheim</option>
        <option value="322" title="Verbandsgemeinde Daun">VG Daun</option>
        <option value="874" title="Verbandsgemeinde Dierdorf">VG Dierdorf</option>
        <option value="468" title="Verbandsgemeinde Diez">VG Diez</option>
        <option value="830" title="Verbandsgemeide Emmelshausen">VG Emmelshausen</option>
        <option value="142" title="Verbandsgemeinde Gebhardshain (0713205)">VG Gebhardshain</option>
        <option value="202" title="Verbandsgemeinde Gerolstein">VG Gerolstein</option>
        <option value="383" title="Verbandsgemeinde Hamm">VG Hamm</option>
        <option value="578" title="Verbandsgemeinde Heßheim">VG Heßheim</option>
        <option value="197" title="Verbandsgemeinde Hillesheim">VG Hillesheim</option>
        <option value="829" title="Verbandsgemeide Kastellaun">VG Kastellaun</option>
        <option value="420" title="Verbandsgemeinde Kelberg">VG Kelberg</option>
        <option value="828" title="Verbandsgemeide Kirchberg">VG Kirchberg</option>
        <option value="396" title="Verbandsgemeinde Kirchen">VG Kirchen</option>
        <option value="904" title="Verbandsgemeinde Konz">VG Konz</option>
        <option value="173" title="Verbandsgemeinde Landau-Land">VG Landau-Land</option>
        <option value="941" title="Verbandsgemeinde Linz">VG Linz</option>
        <option value="245" title="Verbandsgemeinde Maifeld">VG Maifeld</option>
        <option value="584" title="Verbandsgemeinde Maxdorf">VG Maxdorf</option>
        <option value="352" title="Verbandsgemeinde Mendig">VG Mendig</option>
        <option value="199" title="Verbandsgemeinde Nassau">VG Nassau</option>
        <option value="469" title="Verbandsgemeinde Nastätten">VG Nastätten</option>
        <option value="195" title="Verbandsgemeinde Obere Kyll">VG Obere Kyll</option>
        <option value="188" title="Verbandsgemeinde Pellenz">VG Pellenz</option>
        <option value="970" title="Verbandsgemeinde Prüm">VG Prüm</option>
        <option value="407" title="Verbandsgemeinde Ransbach-Baumbach">VG Ransbach-Baumbach</option>
        <option value="382" title="Verbandsgemeinde Rennerod">VG Rennerod</option>
        <option value="85" title="Verbandsgemeinde Rhein-Mosel">VG Rhein-Mosel</option>
        <option value="588" title="Verbandsgemeinde Rheinauen">VG Rheinauen</option>
        <option value="826" title="Verbandsgemeide Rheinböllen">VG Rheinböllen</option>
        <option value="625" title="Verbandsgemeinde Rhens">VG Rhens</option>
        <option value="574" title="Verbandsgemeinde Römerberg-Dudenhofen">VG Römerberg-Dudenhofen</option>
        <option value="217" title="Verbandsgemeinde Rülzheim">VG Rülzheim</option>
        <option value="827" title="Verbandsgemeinde Sankt Goar-Oberwesel">VG Sankt Goar - Oberwesel</option>
        <option value="697" title="Verbandsgemeinde Schweich an der Römischen Weinstraße">VG Schweich</option>
        <option value="919" title="Verbandsgemeinde Selters">VG Selters</option>
        <option value="825" title="Verbandsgemeide Simmern">VG Simmern</option>
        <option value="870" title="Verbandsgemeinde Speicher">VG Speicher</option>
        <option value="1009" title="Verbandsgemeinde Südeifel">VG Suedeifel</option>
        <option value="987" title="Verbandsgemeinde Thalfang am Erbeskopf">VG Thalfang am Erbeskopf</option>
        <option value="988" title="Verbandsgemeinde Traben-Trabach">VG Traben-Trabach</option>
        <option value="346" title="Verbandsgemeinde Vallendar">VG Vallendar</option>
        <option value="591" title="Verbandsgemeinde Vordereifel">VG Vordereifel</option>
        <option value="560" title="Verbandsgemeinde Waldbreitbach">VG Waldbreitbach</option>
        <option value="345" title="Verbandsgemeinde Wallmerod">VG Wallmerod</option>
        <option value="323" title="Verbandsgemeinde Weißenthurm">VG Weißenthurm</option>
        <option value="701" title="Verbandsgemeinde Wissen">VG Wissen</option>
        <option value="989" title="Verbandsgemeinde Wittlich-Land">VG Wittlich-Land</option>
        <option value="40" title="Zentrale Stelle GDI-RP">Zentrale Stelle GDI-RP</option>
      </select>
      <span class="reset-select -js-reset-select" data-target="<?php echo $n ?>-registratingDepartments">Auswahl zurücksetzen</span>
    </div>

    <div id="<?php echo $n ?>-search-extended-what" class="-js-content search-filter">
      <div class="filter-group">Ressourcentypen:</div>
      <div class="inline">
        <input name="checkResourcesWms" id="<?php echo $n ?>-checkResourcesWms" type="checkbox" value="wms">
        <label for="<?php echo $n ?>-checkResourcesWms">Interaktive Karten</label>
      </div>
      <div class="inline">
        <input name="checkResourcesWfs" id="<?php echo $n ?>-checkResourcesWfs" type="checkbox" value="wfs">
        <label for="<?php echo $n ?>-checkResourcesWfs">Such/Download/Erfassungsmodule</label>
      </div>
      <div class="inline">
        <input name="checkResourcesWmc" id="<?php echo $n ?>-checkResourcesWmc" type="checkbox" value="wmc">
        <label for="<?php echo $n ?>-checkResourcesWmc">Kartensammlungen</label>
      </div>
      <div class="inline">
        <input name="checkResourcesDataset" id="<?php echo $n ?>-checkResourcesDataset" type="checkbox" value="dataset">
        <label for="<?php echo $n ?>-checkResourcesDataset">GeoRSS Newsfeeds</label>
      </div>
    </div>
  </form>
    <button class='search--submit -js-search-start'>Suchen</button>
</div>
