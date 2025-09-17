$(function(){
    // 原本ファイルによって原本有無を自動切り替え
    $('#original_file').on('change', function(){
        if(this.files.length > 0){
            $('input[name="'+hasOrgFiledCode+'"][value*="無"]').prop('checked',false);
            $('input[name="'+hasOrgFiledCode+'"][value*="無"]').attr('checked',false);
            $('input[name="'+hasOrgFiledCode+'"][value*="有"]').prop('checked',true);
            $('input[name="'+hasOrgFiledCode+'"][value*="有"]').attr('checked',true);
        }else{
            $('input[name="'+hasOrgFiledCode+'"][value*="有"]').prop('checked',false);
            $('input[name="'+hasOrgFiledCode+'"][value*="有"]').attr('checked',false);
            $('input[name="'+hasOrgFiledCode+'"][value*="無"]').prop('checked',true);
            $('input[name="'+hasOrgFiledCode+'"][value*="無"]').attr('checked',true);
        };
    });
});