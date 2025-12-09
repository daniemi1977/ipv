function ipvBulkRun(type){
    const ids=[...document.querySelectorAll('.ipv-bulk-select:checked')].map(e=>e.value);
    const log=document.getElementById('ipv-bulk-log');
    const bar=document.getElementById('ipv-progress-bar');
    log.innerHTML+="Starting "+type+"...\n";

    let data=new FormData();
    data.append('action','ipv_bulk_action');
    data.append('bt',type);
    ids.forEach(id=>data.append('ids[]',id));

    fetch(ajaxurl,{method:'POST',body:data})
    .then(r=>r.text())
    .then(t=>{
        log.innerHTML+=t+"\n";
        bar.style.width="100%";
    });
}
