// 批量上传进度显示
function uploadProgress(file, progress) {
    let progressBar = document.getElementById("progress-" + file.name);
    if (!progressBar) {
        let progressDiv = document.createElement("div");
        progressDiv.id = "progress-" + file.name;
        progressDiv.style.margin = "5px 0";
        progressDiv.style.padding = "8px";
        progressDiv.style.background = "#f8f8f8";
        progressDiv.style.borderRadius = "10px";
        progressDiv.innerHTML = `
            <span>${file.name}</span>
            <div style="height:8px; background:#eee; border-radius:4px; margin-top:5px;">
                <div id="bar-${file.name}" style="height:100%; width:0%; background:#ff69b4; border-radius:4px;"></div>
            </div>
            <span id="percent-${file.name}">0%</span>
        `;
        document.getElementById("upload-progress").appendChild(progressDiv);
        progressBar = document.getElementById("bar-" + file.name);
    }
    progressBar.style.width = progress + "%";
    document.getElementById("percent-" + file.name).innerText = progress + "%";
}
