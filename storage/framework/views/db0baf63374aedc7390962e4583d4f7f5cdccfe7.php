<?php $__env->startComponent('mail::message'); ?>
# Hello, <?php echo e($data['approver_name']); ?>


<?php echo e($data['form_name']); ?> form added you as an approver.

To approve or reject this request, jus click <b>Form Link</b>
and enter this password <b><?php echo e($data['password']); ?></b> please make sure
to not share this password.

<?php $__env->startComponent('mail::button', ['url' => $data['link'], 'color' => 'red']); ?>
    Form Link
<?php echo $__env->renderComponent(); ?>


Fur further assistance, please send us an email at <b>sdmd@usep.edu.ph</b>


Thank you.


If you are not the intended recipient, please contact the sender
and ignore or delete this email.
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\Glyde\Desktop\File\Projects\Request Form\api\resources\views/emails/request-form.blade.php ENDPATH**/ ?>
