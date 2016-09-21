<div class="full-width-container">
 <h1 class="heading">{$description} @ {$host_name}</h1>
 <div class="row">
  <div class="col s9">
 
   <div class="row">
    <div class="col s4 status">
    {if $state == 3}
     <span class="purple-text"><i class="material-icons">report</i> Service is UNKNOWN</span>
    {elseif $state == 2}
     <span class="red-text"><i class="material-icons">report</i> Service is CRITICAL</span>
    {elseif $state == 1}
     <span class="orange-text"><i class="material-icons">warning</i> Service is WARNING</span>
    {elseif $state == 0}
     <span class="green-text"><i class="material-icons">check_circle</i> Service is OK</span>
    {/if}
    </div>
    <div class="col s4"><span data-tooltip="{$last_state_change_date}" data-position="bottom">Since {$last_state_change_intv}</span></div>
    <div class="col s4"><span data-tooltip="{$last_check_date}" data-position="bottom">Last check was {$last_check_intv}</span></div>
   </div>
 
   <div class="card-panel grey lighten-4">{$plugin_output}</div>
   <p>{$long_plugin_output}</p>

   <div class="controls">
    <a class="btn deep-orange lighten-1 waves-effect waves-light" href="#!" data-event="recheck"><i class="material-icons">replay</i> Re-check (force)</a>
    <a class="btn deep-orange lighten-1 waves-effect waves-light" href="javascript:downtime();"><i class="material-icons">alarm_off</i> Schedule a downtime</a>
    <a class="btn deep-orange lighten-1 waves-effect waves-light" href="javascript:comment();"><i class="material-icons">comment</i> Add a comment</a>
   </div>
 
  </div>
  <div class="col s3 details-settings">
 
   {if $notifications_enabled == 1}
    <p><i class="material-icons green-text">check</i> Notifications enabled</p>
   {else}
    <p><i class="material-icons red-text">cross</i> Notifications disabled</p>
   {/if}

   {if $active_checks_enabled == 1}
    <p><i class="material-icons green-text">check</i> Active checks enabled</p>
   {else}
    <p><i class="material-icons red-text">cross</i> Active checks disabled</p>
   {/if}

   <br />

   <table class="bordered">
    <tr>
     <td>Check interval</td>
     <td class="right-align">{$check_interval}</td>
    </tr>
    <tr>
     <td>Retry interval</td>
     <td class="right-align">{$retry_interval}</td>
    </tr>
    <tr>
     <td>Notification interval</td>
     <td class="right-align">{$notification_interval}</td>
    </tr>
   </table>
 
  </div>
 </div>
</div>
<script type="text/javascript" src="/static/js/details.js"></script>
