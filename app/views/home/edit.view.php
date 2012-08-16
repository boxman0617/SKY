<h1>Edit <?= $id; ?></h1>
<h2><?= $city; ?></h2>
<form action="/home/<?= $id; ?>" method="post">
    <?= Controller::MethodPut(); ?>
    <input type="text" name="home[name]"/>
    <input type="submit" value="submit" name="submit"/>
</form>
<ul>
    <li><a href="/" >Back</a></li>
</ul>