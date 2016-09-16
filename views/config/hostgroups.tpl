<div class="container">
 <h1 class="heading">Host Groups</h1>
 <table class="striped highlight bordered condensed">
  <thead>
   <tr>
    <th>Group</th>
    <th>Members</th>
   </tr>
  </thead>
  <tbody>
   {loop $groups}
    <tr>
     <td class="tooltiped" data-position="bottom" data-delay="50" data-tooltip="{$notes}">{$name}</td>
     <td>{$members}</td>
    </tr>
   {/loop}
  </tbody>
 </table>
</div>

<script type="text/javascript">
$('.tooltiped').tooltip();
</script>
