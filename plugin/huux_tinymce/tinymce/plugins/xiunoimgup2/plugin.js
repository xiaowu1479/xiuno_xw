tinymce.PluginManager.add("xiunoimgup", function(editor, url) {
    var pluginName = '多图片上传';
    var RAWG_API_KEY = '1b8cdcce96724b248b6cdc31b45f81c8';
    var RAWG_API_URL = 'https://api.rawg.io/api/games';
    
    // Steam API 配置（请替换为你的Steam API密钥）
    var STEAM_API_KEY = 'YOUR_STEAM_API_KEY'; // 从https://partner.steamgames.com/获取
    var STEAM_STORE_API_URL = 'https://store.steampowered.com/api/appdetails';
    var STEAM_SEARCH_API_URL = 'https://api.steampowered.com/ISteamApps/GetAppList/v2/';

    // 图片上传功能
    var upimg = function() {
        var input = document.createElement('input');
        var postform = document.getElementById("form");
        input.setAttribute('type', 'file');
        input.setAttribute('multiple', 'multiple');
        input.setAttribute('accept', 'image/*');
        input.setAttribute('style', 'display:none');
        postform.appendChild(input); 
        input.addEventListener("change", function(e) {
            var files = this.files;
            $.each_sync(files, function(i, callback) {
                var file = files[i];
                xn.upload_file(file, xn.url("attach-create"), {
                    is_image: 1
                }, function(code, json) {
                    if (code == 0) {
                        var s = '<img src="' + json.url + '" width="' + json.width + '" height=\"' + json.height + '\" />';
                        editor.insertContent(s);
                    } else {
                        console.log('上传失败\n');
                    }
                    callback();
                });
            });
        }, false);
        input.click();
    }

    // RAWG游戏搜索功能
    var getGame = function() {
        editor.windowManager.open({
            title: '获取游戏信息',
            body: [
                {
                    type: 'textbox',
                    name: 'searchQuery',
                    label: '搜索游戏',
                    placeholder: '输入游戏名称搜索...'
                },
                {
                    type: 'container',
                    name: 'gameResults',
                    label: '搜索结果',
                    html: '<div id="gameResultsContainer" style="max-height: 300px; overflow-y: auto; margin-top: 10px;"></div>'
                }
            ],
            onsubmit: function(e) {
                const query = e.data.searchQuery.trim();
                if (!query) return;

                fetch(`${RAWG_API_URL}?key=${RAWG_API_KEY}&search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultsContainer = document.getElementById('gameResultsContainer');
                        resultsContainer.innerHTML = '';

                        if (data.results && data.results.length > 0) {
                            data.results.forEach(game => {
                                const gameItem = document.createElement('div');
                                gameItem.className = 'game-item';
                                gameItem.style = 'padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;';
                                gameItem.innerHTML = `
                                    <div style="display: flex; align-items: center;">
                                        <img src="${game.background_image || 'https://via.placeholder.com/80'}" 
                                             style="width: 80px; height: 60px; object-fit: cover; margin-right: 10px;" 
                                             alt="${game.name}">
                                        <div>
                                            <strong>${game.name}</strong>
                                            <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                                                发布日期: ${game.released || '未知'} | 评分: ${game.rating || '暂无'}
                                            </p>
                                        </div>
                                    </div>
                                `;
                                gameItem.addEventListener('click', () => {
                                    insertGameToEditor(game);
                                    editor.windowManager.close();
                                });
                                resultsContainer.appendChild(gameItem);
                            });
                        } else {
                            resultsContainer.innerHTML = '<p style="color: #999; text-align: center;">未找到匹配的游戏</p>';
                        }
                    })
                    .catch(error => {
                        console.error('API 请求失败:', error);
                        document.getElementById('gameResultsContainer').innerHTML = 
                            '<p style="color: #ff0000; text-align: center;">获取数据失败，请重试</p>';
                    });
            }
        });
    }

    // 插入RAWG游戏信息到编辑器
    function insertGameToEditor(game) {
        const gameHtml = `
            <div class="game-info">
                <img src="${game.background_image || 'https://via.placeholder.com/120x180'}" 
                     alt="${game.name}" 
                     style="width: 120px; height: 180px; object-fit: cover; border-radius: 5px; margin-right: 15px;">
                <div class="game-info-content">
                    <h2>${game.name} 
                        <span class="badge-vlo1">评分: ${game.rating || '暂无'}</span>
                    </h2>
                    <p><strong>发布日期:</strong> ${game.released || '未知'}</p>
                    <p><strong>平台:</strong> ${game.platforms?.map(p => p.platform.name).join(', ') || '未知'}</p>
                    <p><strong>简介:</strong> ${game.description_raw ? game.description_raw.substring(0, 200) + '...' : '暂无简介'}</p>
                    <p><a href="${game.metacritic_url || '#'}" target="_blank" style="color: #0084ff;">查看详情</a></p>
                </div>
            </div>
            <p></p>
        `;
        editor.insertContent(gameHtml);
    }

    // Steam游戏搜索功能
    var getSteamGame = function() {
        editor.windowManager.open({
            title: '获取Steam游戏信息',
            body: [
                {
                    type: 'textbox',
                    name: 'steamSearchQuery',
                    label: '搜索Steam游戏',
                    placeholder: '输入游戏名称搜索...'
                },
                {
                    type: 'container',
                    name: 'steamGameResults',
                    label: '搜索结果',
                    html: '<div id="steamResultsContainer" style="max-height: 300px; overflow-y: auto; margin-top: 10px;"></div>'
                }
            ],
            onsubmit: function(e) {
                const query = e.data.steamSearchQuery.trim();
                if (!query) return;

                fetch(`${STEAM_SEARCH_API_URL}?key=${STEAM_API_KEY}`)
                    .then(response => response.json())
                    .then(data => {
                        const matchedGames = data.applist.apps.filter(app => 
                            app.name.toLowerCase().includes(query.toLowerCase())
                        ).slice(0, 10);

                        const resultsContainer = document.getElementById('steamResultsContainer');
                        resultsContainer.innerHTML = '';

                        if (matchedGames.length > 0) {
                            matchedGames.forEach(game => {
                                fetch(`${STEAM_STORE_API_URL}?appids=${game.appid}&cc=cn&l=schinese`)
                                    .then(res => res.json())
                                    .then(detail => {
                                        const gameData = detail[game.appid];
                                        if (!gameData || !gameData.success) return;

                                        const data = gameData.data;
                                        const gameItem = document.createElement('div');
                                        gameItem.className = 'game-item';
                                        gameItem.style = 'padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;';
                                        gameItem.innerHTML = `
                                            <div style="display: flex; align-items: center;">
                                                <img src="${data.header_image || 'https://via.placeholder.com/80'}" 
                                                     style="width: 80px; height: 60px; object-fit: cover; margin-right: 10px;" 
                                                     alt="${data.name}">
                                                <div>
                                                    <strong>${data.name}</strong>
                                                    <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                                                        发布日期: ${data.release_date?.date || '未知'} | 类型: ${data.genres?.map(g => g.description).join(', ') || '未知'}
                                                    </p>
                                                </div>
                                            </div>
                                        `;
                                        gameItem.addEventListener('click', () => {
                                            insertSteamGameToEditor(data);
                                            editor.windowManager.close();
                                        });
                                        resultsContainer.appendChild(gameItem);
                                    });
                            });
                        } else {
                            resultsContainer.innerHTML = '<p style="color: #999; text-align: center;">未找到匹配的游戏</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Steam API 请求失败:', error);
                        document.getElementById('steamResultsContainer').innerHTML = 
                            '<p style="color: #ff0000; text-align: center;">获取数据失败，请重试</p>';
                    });
            }
        });
    }

    // 插入Steam游戏信息到编辑器
    function insertSteamGameToEditor(game) {
        const gameHtml = `
            <div class="game-info steam-game">
                <img src="${game.header_image || 'https://via.placeholder.com/120x180'}" 
                     alt="${game.name}" 
                     style="width: 120px; height: 180px; object-fit: cover; border-radius: 5px; margin-right: 15px;">
                <div class="game-info-content">
                    <h2>${game.name} 
                        <span class="badge-vlo2">价格: ${game.price_overview?.final_formatted || '未知'}</span>
                    </h2>
                    <p><strong>发布日期:</strong> ${game.release_date?.date || '未知'}</p>
                    <p><strong>开发商:</strong> ${game.developers?.join(', ') || '未知'}</p>
                    <p><strong>发行商:</strong> ${game.publishers?.join(', ') || '未知'}</p>
                    <p><strong>简介:</strong> ${game.short_description ? game.short_description.substring(0, 200) + '...' : '暂无简介'}</p>
                    <p><a href="https://store.steampowered.com/app/${game.steam_appid}" target="_blank" style="color: #0084ff;">查看Steam商店页</a></p>
                </div>
            </div>
            <p></p>
        `;
        editor.insertContent(gameHtml);
    }

    // 注册图片上传按钮
    editor.ui.registry.addButton("xiunoimgup", {
        icon: "gallery",
        tooltip: pluginName,
        onAction: upimg
    });

    // 注册RAWG游戏按钮
    editor.ui.registry.addButton("xiunogame", {
        icon: "game",
        tooltip: "插入游戏信息",
        onAction: getGame
    });

    // 注册Steam游戏按钮
    editor.ui.registry.addButton("xiunosteamgame", {
        icon: "sourcecode",
        tooltip: "插入Steam游戏信息",
        onAction: getSteamGame
    });

    // 注册菜单项
    editor.ui.registry.addMenuItem("xiunoimgup", {
        icon: "gallery",
        text: "多图片上传",
        onAction: upimg
    });

    editor.ui.registry.addMenuItem("xiunogame", {
        icon: "game",
        text: "插入游戏信息",
        onAction: getGame
    });

    editor.ui.registry.addMenuItem("xiunosteamgame", {
        icon: "sourcecode",
        text: "插入Steam游戏信息",
        onAction: getSteamGame
    });

    return {
        getMetadata: function() {
            return {
                name: "多图片上传及游戏功能",
                url: "http://www.huux.cc",
            }
        }
    }
});
    