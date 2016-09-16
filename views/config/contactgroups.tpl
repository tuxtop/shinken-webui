<div class="container">
 <h1 class="heading">Contact Groups</h1>
 <table class="striped highlight bordered condensed">
  <thead>
   <tr>
    <th>Group</th>
    <th>Alias</th>
    <th>Members</th>
   </tr>
  </thead>
  <tbody>
   {loop $groups}
    <tr>
     <td>{$name}</td>
     <td>{$alias}</td>
     <td>{$members}</td>
    </tr>
   {/loop}
  </tbody>
 </table>
</div>
