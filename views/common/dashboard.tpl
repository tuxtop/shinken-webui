<div class="container dashboard">

 <h1 class="heading">Dashboard</h1>
 <div class="row center-align dashboard-icons">
  <div class="col s3"></div>
  <div class="col s2">

   <p><i class="material-icons">storage</i></p>
   <p>Hosts</p>
   <p><span class="chip {$stats.hosts.errors.style}">{$stats.hosts.errors.percent}%</span></p>
   <p class="grey-text">{$stats.hosts.number}</p>

  </div>
  <div class="col s2">

   <p><i class="material-icons">network_check</i></p>
   <p>Services</p>
   <p><span class="chip {$stats.hosts.errors.style}">{$stats.hosts.errors.percent}%</span></p>
   <p class="grey-text">{$stats.services.number}</p>

  </div>
  <div class="col s2">

   <p><i class="material-icons">business</i></p>
   <p>Business impact</p>
   <p><span class="chip {$business.style}">{$business.text}</span></p>

  </div>
  <div class="col s3"></div>
 </div>

 <!--<h2>Daemons status</h2>
 <div class="row">
  <div class="col s3">
   <table class="striped highlight">
    <thead>
     <tr>
      <th>Daemon</th>
      <th>Type</th>
      <th>Status</th>
     </tr>
    </thead>
    <tbody>
     {loop $daemons}
     <tr>
      <td>{$name}</td>
      <td>{$type}</td>
      <td><i class="material-icons {$status.color}">{$status.ico}</i></td>
     </tr>
     {/loop}
    </tbody>
   </table>
  </div>
  <div class="col s9"></div>
 </div>-->

</div>
