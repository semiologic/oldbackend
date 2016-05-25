<?php
if ( $GLOBALS['cmd'] != 'login' ) :
?>
</div>
</div><!-- #content -->
<div id="sidebar">
<div class="pad">
<?php
if ( isset($_SESSION['user_id']) && !$_SESSION['is_admin'] ) :
?>
<ul>
	<li><h2>Members</h2>
	<ul>
		<li><a href="user.php">Profile</a></li>
	</ul>
	</li>
	<li><h2>Customers</h2>
	<ul>
		<li><a href="memberships.php">Memberships</a></li>
		<li><a href="orders.php">Financials</a></li>
		<li><a href="order.php">New Order</a></li>
	</ul>
	</li>
	<li><h2>Affiliates</h2>
	<ul>
		<li><a href="campaigns.php">Campaigns</a></li>
		<li><a href="stats.php">Statistics</a></li>
		<li><a href="payments.php">Payments</a></li>
	</ul>
	</li>
</ul>
<ul>
	<li><a href="logout.php">Logout</a></li>
</ul>
<?php
elseif ( isset($_SESSION['user_id']) ) :
?>
<ul>
	<li><h2>Members</h2>
	<ul>
		<li><a href="user.php">Profile</a></li>
	</ul>
	</li>
	<li><h2>Customers</h2>
	<ul>
		<li><a href="finance.php">Financials</a></li>
		<li><a href="orders.php">Orders</a></li>
	</ul>
	</li>
	<li><h2>Affiliates</h2>
	<ul>
		<li><a href="stats.php">Statistics</a></li>
		<li><a href="payments.php">Payments</a></li>
	</ul>
	</li>
</ul>
<ul>
	<li><a href="logout.php">Logout</a></li>
</ul>
<?php
else :
?>
<ul>
	<li><a href="register.php">Register</a></li>
	<li><a href="login.php">Login</a></li>
</ul>
<?php
endif;
?>
</div>
</div><!-- #sidebar-->
<div style="clear: both"></div>
<div id="footer">
<div class="pad">
	&copy; Mesoconcepts, 2005-<?php echo date('Y'); ?>
</div>
</div>
</div><!-- #wrapper -->
<?php
endif;
?>
</body>
</html>
<?php
die;
?>