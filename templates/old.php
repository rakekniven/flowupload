<?php
    \OCP\User::checkLoggedIn();
    
    //\OCP\Util::addScript('flowupload', 'vue.min');
    \OCP\Util::addScript('flowupload', 'vue');
    \OCP\Util::addScript('flowupload', 'flow.min');
    \OCP\Util::addScript('flowupload', 'app');
    \OCP\Util::addStyle('flowupload', 'style');
?>
<div id="app" role="main">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

  <!-- APP NAVIAGTION -->
  <div id="app-navigation">
    <div class="app-navigation-new">
        <ul>
            <li id="app-navigation-entry-utils-create" v-on:click="pickNewLocation()" class="app-navigation-entry-utils-menu-button">
              <button class="icon-add"><?= htmlspecialchars($l->t('New destination')); ?></button>
            </li>
        </ul>
    </div>
    <ul id="locations" class="with-icon">
      <li class="fileDropZone" v-for="location in locations" v-bind:key="location.id" v-bind:id="'location-' + location.path">
        <a ng-href="" class="icon-folder" v-on:click="switchActiveLocationById(location.id)" v-bind:title="location.path">{{location.path}}</a>
        <div class="app-navigation-entry-utils">
            <ul>
                <li class="app-navigation-entry-utils-counter" v-bind:title="location.flow.files.length + '<?= htmlspecialchars($l->t('Files')); ?>'">{{ location.flow.files.length }}</li>
                <li class="app-navigation-entry-utils-menu-button"><button></button></li>
            </ul>
        </div>
        <div appNavigationEntryMenu>
            <ul>
                <li>
                    <a v-bind:href="'/index.php/apps/files/?dir=' + location.path" target="_blank" rel="noopener noreferrer">
                        <span class="icon-files"></span>
                        <span><?= htmlspecialchars($l->t('Open')); ?></span>
                    </a>
                </li>
                <li v-on:click="toggleStarredLocation(location.path)">
                    <a href="">
                        <span class="icon-starred"></span>
                        <span v-if="!location.starred"><?= htmlspecialchars($l->t('Star')); ?></span>
                        <span v-if="location.starred"><?= htmlspecialchars($l->t('Unstar')); ?></span>
                    </a>
                </li>
                <li v-on:click="removeLocation(location.path)">
                    <a href="">
                        <span class="icon-delete"></span>
                        <span><?= htmlspecialchars($l->t('Remove')); ?></span>
                    </a>
                </li>
            </ul>
        </div>
      </li>
    </ul>

  <div id="app-settings">
        <div id="app-settings-header">
            <button class="settings-button"
                    data-apps-slide-toggle="#app-settings-content">
                <?= htmlspecialchars($l->t('Settings')); ?>
            </button>
        </div>
        <div id="app-settings-content">
            <!-- Your settings content here -->
        </div>
    </div>
  </div>
  
  <div class="fileDropZone" id="app-content" style="padding: 2.5%; width:auto">
    <div id="noLocationSelected" v-show="activeLocation === undefined && loaded"><?= $l->t('Please select a location'); ?></div>
    <div id="locationSelected" ng-cloak v-show="activeLocation != undefined">
        <h2 id="title"><?= htmlspecialchars($l->t('Transfers')); ?></h2>

        <div class="buttonGroup">
          <span class="uploadSelectButton button" uploadtype="file">
            <span class="icon icon-file select-file-icon" style=""></span>
            <span><?= htmlspecialchars($l->t('Select File')); ?></span>
          </span>
          <input id="FileSelectInput" type="file" multiple="multiple">
          <span class="uploadSelectButton button" uploadtype="folder" v-show="activeLocation.flow?.supportDirectory">
            <span class="icon icon-files" style="background-image: var(--icon-files-000);"></span>
            <span><?= htmlspecialchars($l->t('Select Folder')); ?></span>
          </span>
          <input id="FolderSelectInput" type="file" multiple="multiple" webkitdirectory="webkitdirectory">
        </div>

        <hr>

        <div class="buttonGroup">
          <a class="button" v-on:click="activeLocation.flow?.resume()">
              <span class="icon icon-play"></span>
              <span><?= htmlspecialchars($l->t('Start/Resume')); ?></span>
          </a>
          <a class="button" v-on:click="activeLocation.flow?.pause()">
              <span class="icon icon-pause"></span>
              <span><?= htmlspecialchars($l->t('Pause')); ?></span>
          </a>
          <a class="button" v-on:click="activeLocation.flow?.cancel()">
              <span class="icon icon-close"></span>
              <span><?= htmlspecialchars($l->t('Cancel')); ?></span>
          </a>
          <a id="hideFinishedButton" class="button" v-on:click="hideFinished = !hideFinished">
            <input type="checkbox" ng-model="hideFinished"></input>
            <span><?= htmlspecialchars($l->t('Hide finished uploads')); ?></span>
          </a>
        </div>

        <hr>

        <p>
          <span class="label"><?= $l->t('Size'); ?>: {{activeLocation.flow?.getSize() | bytes}}</span>
          <span class="label" v-if="activeLocation.flow?.getFilesCount() != 0"><?= htmlspecialchars($l->t('Progress')); ?>: {{ trimDecimals(activeLocation.flow?.progress()*100, 2) }}%</span>
          <span class="label" v-if="activeLocation.flow?.isUploading()"><?= htmlspecialchars($l->t('Time remaining')); ?>: {{activeLocation.flow?.timeRemaining() | seconds}}</span>
          <span class="label" v-if="activeLocation.flow?.isUploading()"><?= htmlspecialchars($l->t('Uploading')); ?>...</span>
        </p>

        <hr>

        <table id="uploadsTable">
          <thead>
          <tr>
            <th class="hideOnMobile" style="width:5%">
              <span class="noselect">#</span>
            </th>
            <th ng-click="tableSortClicked('relativePath')">
              <a class="noselect">
                <span><?= htmlspecialchars($l->t('Name')); ?></span>
                <span ng-class="{'icon-triangle-n':  (sortType == 'relativePath' && sortReverse), 'icon-triangle-s': (sortType == 'relativePath' && !sortReverse)}" class="sortIndicator"></span>
              </a>
            </th>
            <th></th>
            <th class="hideOnMobile" ng-click="tableSortClicked('-currentSpeed')" style="width:10%">
              <a class="noselect">
                <span><?= htmlspecialchars($l->t('Uploadspeed')); ?></span>
                <span ng-class="{'icon-triangle-n':  (sortType == '-currentSpeed' && sortReverse), 'icon-triangle-s': (sortType == '-currentSpeed' && !sortReverse)}" class="sortIndicator"></span>
              </a>
            </th>
            <th ng-click="tableSortClicked('-size')" style="width:10%">
              <a class="noselect">
                <span><?= htmlspecialchars($l->t('Size')); ?></span>
                <span ng-class="{'icon-triangle-n':  (sortType == '-size' && sortReverse), 'icon-triangle-s': (sortType == '-size' && !sortReverse)}" class="sortIndicator"></span>
              </a>
            </th>
            <th ng-click="tableSortClicked('-progress()')" style="width:20%">
              <a class="noselect">
                <span><?= htmlspecialchars($l->t('Progress')); ?></span>
                <span ng-class="{'icon-triangle-n':  (sortType == '-progress()' && sortReverse), 'icon-triangle-s': (sortType == '-progress()' && !sortReverse)}" class="sortIndicator"></span>
              </a>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr v-if="!(file.isComplete() && hideFinished)" v-for="file in filteredFiles">
            <td class="hideOnMobile">{{$index+1}}</td>
            <td class="ellipsis" v-bind:title="'UID: ' + file.uniqueIdentifier">
                <span>{{file.relativePath}}</span>
            </td>
            <td>
              <div class="actions" v-if="!file.isComplete() || file.error">
                <a class="action permanent" title="<?= htmlspecialchars($l->t('Resume')); ?>" v-click="file.resume()" v-if="!file.isUploading() && !file.error">
                  <span class="icon icon-play"></span>
                </a>
                <a class="action permanent" title="<?= htmlspecialchars($l->t('Pause')); ?>" v-click="file.pause()" v-if="file.isUploading() && !file.error">
                  <span class="icon icon-pause"></span>
                </a>
                <a class="action permanent" title="<?= htmlspecialchars($l->t('Retry')); ?>" v-click="file.retry()" v-show="file.error">
                  <span class="icon icon-play"></span>
                </a>
                <a class="action permanent" title="<?= htmlspecialchars($l->t('Cancel')); ?>" v-click="file.cancel()">
                  <span class="icon icon-close"></span>
                </a>
              </div>
            </td>
            <td class="hideOnMobile">
                <span v-if="file.isUploading()">{{file.currentSpeed | byterate}}</span>
            </td>
            <td title="'Chunks: ' + file | completedChunks + ' / ' + file.chunks.length">
                <span class="hideOnMobile" v-if="!file.isComplete()">{{file.size*file.progress() | bytes}}/</span>
                <span>{{file.size | bytes}}</span>
            </td>
            <td>
              <progress v-if="!file.isComplete() && !file.error" class="progressbar hideOnMobile" max="1" v-bind:value="file.progress()"></progress>
              <span v-if="!file.isComplete() && !file.error">{{ trimDecimals(file.progress()*100, 2) }}%</span>
              <span v-if="file.isComplete() && !file.error"><?= htmlspecialchars($l->t('Completed')); ?></span>
              <span v-if="file.error"><?= htmlspecialchars($l->t('Error')); ?></span>
            </td>
          </tr>
          </tbody>
        </table>

        <p id="homeDirectoryLink"><a href="../files?dir=%2Fflowupload"><?= htmlspecialchars($l->t('The files will be saved in your home directory.')); ?></a></p>
      <div>
      </div>
 </div>
