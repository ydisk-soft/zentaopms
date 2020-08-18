<?php include '../../common/view/header.html.php';?>
<?php include '../../common/view/kindeditor.html.php';?>
<div id="mainContent" class="main-content fade">
  <div class="center-block">
    <div class="main-header">
      <h2><?php echo $lang->risk->create;?></h2>
    </div>
    <form class="load-indicator main-form form-ajax" method='post' enctype='multipart/form-data' id='dataform'>
      <table class="table table-form">
        <tbody>
          <tr>
            <th><?php echo $lang->risk->source;?></th>
            <td><?php echo html::select('source', $lang->risk->sourceList, '', "class='form-control chosen'");?></td>
            <td></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->name;?></th>
            <td><?php echo html::input('name', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->category;?></th>
            <td><?php echo html::select('category', $lang->risk->categoryList, '', "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->strategy;?></th>
            <td><?php echo html::select('strategy', $lang->risk->strategyList, '', "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->impact;?></th>
            <td><?php echo html::select('impact', $lang->risk->impactList, 3, "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->probability;?></th>
            <td><?php echo html::select('probability', $lang->risk->probabilityList, 3, "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->riskindex;?></th>
            <td><?php echo html::input('riskindex', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->pri;?></th>
            <td><?php echo html::select('pri', $lang->risk->priList, '', "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->plannedClosedDate;?></th>
            <td><?php echo html::input('plannedClosedDate', '', "class='form-control form-date'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->assignedTo;?></th>
            <td><?php echo html::select('assignedTo', $users, '', "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->prevention;?></th>
            <td colspan='2'><?php echo html::textarea('prevention', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->risk->remedy;?></th>
            <td colspan='2'><?php echo html::textarea('remedy', '', "class='form-control'");?></td>
          </tr>
          <tr>
            <td colspan='3' class='form-actions text-center'>
              <?php echo html::submitButton() . html::backButton();?>
            </td>
          </tr>
        </tbody>
      </table>
    </form>
  </div>
</div>
<?php include '../../common/view/footer.html.php';?>
