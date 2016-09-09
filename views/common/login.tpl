{if $message}
<div class="card-panel {$message.style}">
 {$message.text}
</div>
{/if}
<div class="container">
 <div class="card-panel">
  <h1>Sign In</h1>
  <form method="post" action="">
   <p><input type="text" name="username" placeholder="Username" /></p>
   <p><input type="password" name="password" placeholder="Password" /></p>
   <p><input type="submit" value="Sign In" class="btn" /></p>
  </form>
 </div>
</div>
