<div class="container">
 <h1 class="heading">Shinken Status</h1>
 <table class="bordered striped highlight condensed">
  <thead>
   <tr>
    <th>Name</th>
    <th>Type</th>
    <th>Status</th>
   </tr>
  </thead>
  <tbody>
   {loop $daemons}
   <tr>
    <td>{$name}</td>
    <td>{$type}</td>
    <td class="ico"><i class="material-icons {$status.style}-text">{$status.ico}</i></td>
   </tr>
   {/loop}
  </tbody>
 </table>
</div>
