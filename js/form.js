$(function(){
    // 原本ファイルによって原本有無を自動切り替え
    $('#original_file').on('change', function(){
        if(hasOrgFiledType == 'RADIO_BUTTON'){
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
            }
        }else if(hasOrgFiledType == 'DROP_DOWN'){
            $('select[name="'+hasOrgFiledCode+'"] option:selected').attr('selected',false)
            $('select[name="'+hasOrgFiledCode+'"] option:selected').prop('selected',false)
            if(this.files.length > 0){
                $('select[name="'+hasOrgFiledCode+'"]').val('有');
            }else{
                $('select[name="'+hasOrgFiledCode+'"]').val('無');
            }
        }
    });
});