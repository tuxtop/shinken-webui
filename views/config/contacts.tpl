<div class="container">
 <h1 class="heading">Contacts</h1>
 <table class="striped highlight bordered condensed">
  <thead>
   <tr>
    <th rowspan="2">Contact</th>
    <th rowspan="2">Email address</th>
    <th colspan="2">Notifications</th>
    <th rowspan="2">Commands</th>
   </tr>
   <tr>
    <th>Hosts</th>
    <th>Services</th>
   </tr>
  </thead>
  <tbody>
   {loop $contacts}
    <tr>
     <td>{$name}</td>
     <td>{$email}</td>
     <td class="ico">{$notifications.hosts}</td>
     <td class="ico">{$notifications.services}</td>
     <td class="ico">{$commands}</td>
    </tr>
   {/loop}
  </tbody>
 </table>
</div>
