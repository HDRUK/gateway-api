## [2.14.0](https://github.com/HDRUK/gateway-api/compare/v2.13.0...v2.14.0) (2025-07-01)

### ✨ Features

* **GAT-5939:** enable hsts (#1287) ([6dca164](https://github.com/HDRUK/gateway-api/commit/6dca16451eb8131f5e2c25dff7095589d6bb13b0)), closes [GAT-5939](GAT-5939)
* **GAT-6480:** Unit testing for CRUD permission error for Datasets & BioSamples (#1277) ([725090f](https://github.com/HDRUK/gateway-api/commit/725090f051044edd3e18ece8fc47a68016514236)), closes [GAT-6480](GAT-6480)
* **GAT-6482:** Unit testing for CRUD permission error for Datasets & BioSamples via Integrations (#1279) ([c81bd9c](https://github.com/HDRUK/gateway-api/commit/c81bd9c4e0b0337f7c7c73ca0e461e254936f9f6)), closes [GAT-6482](GAT-6482)
* **GAT-6927:** REMOVE all cache and hard coded values from FF (#1278) ([b749e16](https://github.com/HDRUK/gateway-api/commit/b749e169bf91cd630ef6ee76025c90526bb2729a)), closes [GAT-6927](GAT-6927)
* **GAT-6927:** Remove cache (redis) from feature flagging (#1262) ([70a90e8](https://github.com/HDRUK/gateway-api/commit/70a90e8d0e08b156f5222cc78a0a253446a70b5a)), closes [GAT-6927](GAT-6927)
* **GAT-7088:** Create v2 endpoints for use of 'Data Custodian' terminology (#1286) ([0ac689c](https://github.com/HDRUK/gateway-api/commit/0ac689cf1856d68b2b353f372f94ce6334640938)), closes [GAT-7088](GAT-7088)
* **GAT-7097:** Update BE to include user's emails within enquiry emails (#1247) ([873d1f0](https://github.com/HDRUK/gateway-api/commit/873d1f0e7967e37dc988ac4f6c156d46501c8996)), closes [GAT-7097](GAT-7097)
* **GAT-7232:** Merge integration middlewares into mainstream middlewares (#1275) ([cf5902b](https://github.com/HDRUK/gateway-api/commit/cf5902bb51b5814a24280d50ede61f3e2cbd7ec0)), closes [GAT-7232](GAT-7232)
* **GAT-7233:** Merge logic from Integration Dataset Controller into Dataset Controller (#1282) ([1fd2a0b](https://github.com/HDRUK/gateway-api/commit/1fd2a0bc9fcb79936c7f3f0cd543857db55f560c)), closes [GAT-7233](GAT-7233)
* **GAT-7235:** Merge logic from Integration Dur Controller into Dur Controller (#1292) ([e4a7120](https://github.com/HDRUK/gateway-api/commit/e4a71206f19cf7c0fc6523e8b144ccc8eb7c74eb)), closes [GAT-7235](GAT-7235)
* **GAT-7236:** merge tool integrations into tool controller (v1 and v2) (#1290) ([70c5afd](https://github.com/HDRUK/gateway-api/commit/70c5afdcb1647a2b031becdaa63dd4205ae24eae)), closes [GAT-7236](GAT-7236)
* **GAT-7291:** Team email address not receiving notifications (#1283) ([cb2ff3d](https://github.com/HDRUK/gateway-api/commit/cb2ff3de42edbbf2edc7d62c24c6899276f224f6)), closes [GAT-7291](GAT-7291)
* **GAT-7302:** allow custon session header (#1267) ([5f20e94](https://github.com/HDRUK/gateway-api/commit/5f20e943071a111eef59b67f1994b2d45ff31024)), closes [GAT-7302](GAT-7302)
* **GAT-7312:** federation error and history migrations (#1276) ([35a8dad](https://github.com/HDRUK/gateway-api/commit/35a8daddae7167c21fc232354a1b5a099d633f45)), closes [GAT-7312](GAT-7312)
* **GAT-7326:** Change search endpoints to use Data Custodian terminology (#1294) ([7352ad2](https://github.com/HDRUK/gateway-api/commit/7352ad2081248445995053083771ea1162c53e02)), closes [GAT-7326](GAT-7326)
* **GAT-7327:** Add missing endpoints for v2 tools controllers. (#1270) ([1e242d8](https://github.com/HDRUK/gateway-api/commit/1e242d8df26afc5521831a5c56c87f7750ae3da4)), closes [GAT-7327](GAT-7327)
* **GAT-7327:** Add V2 endpoints for Data Use Register (#1281) ([e02b42b](https://github.com/HDRUK/gateway-api/commit/e02b42bee2be2c7d3df3f551cd3ee0e57bb11aaf)), closes [GAT-7327](GAT-7327)
* **GAT-7327:** Further minor fixes to v2 Data Use endpoints (#1284) ([b86992a](https://github.com/HDRUK/gateway-api/commit/b86992ab938f93668b63e0c56255cfbbf3615cb4))

### 🐛 Bug Fixes

* **GAT-6985:** update LinkageExtraction job (#1265) ([a4ce55c](https://github.com/HDRUK/gateway-api/commit/a4ce55ccc36f8feeb89f2206011129db1b008f47)), closes [GAT-6985](GAT-6985)
* **GAT-7193:** Collection 10 contains Datasets & BioSamples that are not active (#1269) ([fee586d](https://github.com/HDRUK/gateway-api/commit/fee586dbbf100aae0c82578ed0d03ad995b98676)), closes [GAT-7193](GAT-7193)
* **GAT-7233:** Fix deleteDataset for elastic observer (#1300) ([5532ae8](https://github.com/HDRUK/gateway-api/commit/5532ae8a698b728a265206cb383f8a2e4710a7da))
* **GAT-7325:** missing sections on dars (#1264) ([b513715](https://github.com/HDRUK/gateway-api/commit/b5137154c36c3f8bbb142bae81fc9fa42c9eece1)), closes [GAT-7325](GAT-7325)
* **GAT-7331:** Move data reads in all artisan commands out of __construct() and into handle(). (#1273) ([4b7937d](https://github.com/HDRUK/gateway-api/commit/4b7937d12a50252cb3656d2fc26a8cf6ae0491ad)), closes [GAT-7331](GAT-7331)
* **GAT-7391:** The search function does not work to filter SAIL datasets by 'keyword' (#1268) ([95eb14f](https://github.com/HDRUK/gateway-api/commit/95eb14f6ab39d15d714131465e22dd283208ce35)), closes [GAT-7391](GAT-7391)
* **GAT-7412:** dataset_id missing off of payload (#1285) ([12b3344](https://github.com/HDRUK/gateway-api/commit/12b3344b7f73ed2128a0d338e7622d978fea0430)), closes [GAT-7412](GAT-7412)
* **GAT-7433:** Fix v1 DUR archiving (#1288) ([245ba2a](https://github.com/HDRUK/gateway-api/commit/245ba2a1772a22dd1ac78626362bf5ae1fc7f707))
* **GAT-7444:**  timeout 408 correct dataset (#1297) ([e20a270](https://github.com/HDRUK/gateway-api/commit/e20a270b97625b241bd9d5f4adef230f1997cbf2)), closes [GAT-7444](GAT-7444)
* **GAT-7444:** 408 error (#1289) ([14ad09e](https://github.com/HDRUK/gateway-api/commit/14ad09e5c3e1a0977ec6baf50b2ed3a0fc9e1299)), closes [GAT-7444](GAT-7444)
* **GAT-7522:** apply restrictions to cohort request endpoints (#1293) ([5e00454](https://github.com/HDRUK/gateway-api/commit/5e00454034476968d862ab3606e71ce38dc6ac4e)), closes [GAT-7522](GAT-7522)
* **GAT-7544:** check user ID matches when calling user endpoint (#1299) ([aac7643](https://github.com/HDRUK/gateway-api/commit/aac7643b7f20e4e2154799cdd15163abbed54ede)), closes [GAT-7544](GAT-7544)

## [2.13.0](https://github.com/HDRUK/gateway-api/compare/v2.12.0...v2.13.0) (2025-06-18)

### ✨ Features

* **GAT-6927:** Remove cache (redis) from feature flagging (#1262) ([ec1b639](https://github.com/HDRUK/gateway-api/commit/ec1b639d33ac6defc53afeae5ced45620548bddb)), closes [GAT-6927](GAT-6927)

## [2.12.0](https://github.com/HDRUK/gateway-api/compare/v2.11.0...v2.12.0) (2025-06-16)

### ✨ Features

* **GAT-6328:** Customer Satisfaction patch and post amendments (#1254) ([7009a3a](https://github.com/HDRUK/gateway-api/commit/7009a3a40e700fba76c7a5f04c2f7b6fce4edbac)), closes [GAT-6328](GAT-6328)
* **GAT-6544:** only one active template allowed at a time (#1261) ([880e69e](https://github.com/HDRUK/gateway-api/commit/880e69eadcf0e96de59d0af5d34237d618066172)), closes [GAT-6544](GAT-6544)
* **GAT-6927:** Temp deadsafe for feature flagging (#1252) ([498e850](https://github.com/HDRUK/gateway-api/commit/498e8500ffcba550556e7faadc68917588801b72)), closes [GAT-6927](GAT-6927)
* **GAT-6995:** Update Extend linkage extraction job (under apps/jobs) for tools - Its not rendering on the Tools Search UI (#1249) ([ae52351](https://github.com/HDRUK/gateway-api/commit/ae52351cb7f446e2020424c2143da470b6945830)), closes [GAT-6995](GAT-6995)
* **GAT-6995:** Update Extend linkage extraction job tools from metadata (#1250) ([4531f12](https://github.com/HDRUK/gateway-api/commit/4531f12bf59ab30ae31a66455d3559799b41fc7f)), closes [GAT-6995](GAT-6995)
* **GAT-7283:** Add option for arrays of questions (#1258) ([d7250c1](https://github.com/HDRUK/gateway-api/commit/d7250c1811c55f1fe21443ddc79e7eedf5cb7e41)), closes [GAT-7283](GAT-7283)

### 🐛 Bug Fixes

* **GAT-1234:** update team aliases (#1253) ([1d661c8](https://github.com/HDRUK/gateway-api/commit/1d661c83be1575159c0c00616dffbb5dfa94e487)), closes [GAT-1234](GAT-1234)
* **GAT-6985:** update LinkageExtraction job (#1265) ([938bb17](https://github.com/HDRUK/gateway-api/commit/938bb17f498264f748d0f69baf9705444e1f611c)), closes [GAT-6985](GAT-6985)
* **GAT-7178:** Incorrect data storage in Enquiry Tables (#1259) ([bef97db](https://github.com/HDRUK/gateway-api/commit/bef97db84282c3c532f272a0f8765a2e3bed994a)), closes [GAT-7178](GAT-7178)
* **GAT-7325:** missing sections on dars (#1264) ([79a7e76](https://github.com/HDRUK/gateway-api/commit/79a7e7618586fe8c30c6096da7b7d2a30978a1e3)), closes [GAT-7325](GAT-7325)

## [2.11.0](https://github.com/HDRUK/gateway-api/compare/v2.10.0...v2.11.0) (2025-06-04)

### ✨ Features

* **GAT-6925:** Enable the use of aliases for Data Custodian names (#1236) ([219645e](https://github.com/HDRUK/gateway-api/commit/219645e391430cdeda4ebc841aee0052b022e81a)), closes [GAT-6925](GAT-6925)
* **GAT-6925:** Enable use of aliases for Data Custodian names for team summary (#1245) ([572d9fd](https://github.com/HDRUK/gateway-api/commit/572d9fd1e1a4b80f625bae018808cc080eb389ea)), closes [GAT-6925](GAT-6925)
* **GAT-6927:** feature flag endpoint for action (#1229) ([eb4cd16](https://github.com/HDRUK/gateway-api/commit/eb4cd1602e41cd3b742926e4764c97a4f2b87060)), closes [GAT-6927](GAT-6927)
* **GAT-6927:** Feature flagging provider (#1242) ([ca97226](https://github.com/HDRUK/gateway-api/commit/ca972269ab9352cd8e14737a59491cb0a1b70d36)), closes [GAT-6927](GAT-6927)
* **GAT-6927:** feature flagging with enquries (#1218) ([afabd68](https://github.com/HDRUK/gateway-api/commit/afabd683072128fd5557ee72635b3821b2ac7e9c)), closes [GAT-6927](GAT-6927)
* **GAT-6993:** Split applications out after submission (#1221) ([30dff8d](https://github.com/HDRUK/gateway-api/commit/30dff8ded05754dcbbbb3dc7e6fe755e1b4c3f0c)), closes [GAT-6993](GAT-6993)
* **GAT-7123:** Add detail to doc based templates (#1238) ([5d276a5](https://github.com/HDRUK/gateway-api/commit/5d276a5895e9338aa1e86e909ad0b33998f192d5)), closes [GAT-7123](GAT-7123)
* **GAT-7207:** Add nhse_sde_approval to Gateway Cohort Request table (#1241) ([92419f7](https://github.com/HDRUK/gateway-api/commit/92419f742b40a193f27fcc59ef71adfc353741a8)), closes [GAT-7207](GAT-7207)
* **GAT-7208:** Add feature flag for Alias (#1243) ([24d0891](https://github.com/HDRUK/gateway-api/commit/24d08917f2767e606deb807cbd59f8a0cf272837)), closes [GAT-7208](GAT-7208)

### 🐛 Bug Fixes

* **GAT-5970:** Collection name - poorly formatted (#1244) ([d060835](https://github.com/HDRUK/gateway-api/commit/d060835b1ffa0302d2a1ac7d9d673df68c790333)), closes [GAT-5970](GAT-5970)
* **GAT-5972:** update v2 tools put and patch (#1232) ([bcc8434](https://github.com/HDRUK/gateway-api/commit/bcc8434bf286404cbd348dbf0a7de8d5ebdef265)), closes [GAT-5972](GAT-5972)
* **GAT-6907:** Getting this error message when creating new team (#1239) ([5d53427](https://github.com/HDRUK/gateway-api/commit/5d53427bb3b2f40fd55bcb0c6e2f382cf6b122ab)), closes [GAT-6907](GAT-6907)
* **GAT-6927:** handle error gracefully for features (#1248) ([28f2057](https://github.com/HDRUK/gateway-api/commit/28f205798fb7c49b1d16b7cbedc97054a362d952)), closes [GAT-6927](GAT-6927)
* **GAT-6927:** more logging and dont define if empty array (#1246) ([dafb5db](https://github.com/HDRUK/gateway-api/commit/dafb5db59961fa1aaa0a74facd5bad4d76673528)), closes [GAT-6927](GAT-6927)
* **GAT-6946:** Unable to view any Datasets & BioSamples - investigate Github issue (#1231) ([1c80899](https://github.com/HDRUK/gateway-api/commit/1c80899aa6a4299005dfc4cf95e033c080c5c526)), closes [GAT-6946](GAT-6946)
* **GAT-6982:** Filter DAR dashboard based on first review, not latest (#1235) ([552606b](https://github.com/HDRUK/gateway-api/commit/552606b7c2346f4a7d2f929246f440b443bd0d93)), closes [GAT-6982](GAT-6982)
* **GAT-6993:** For researcher add related applications to list (#1234) ([3bf83da](https://github.com/HDRUK/gateway-api/commit/3bf83da7334fb38858eaedf54ec458c831e80b68)), closes [GAT-6993](GAT-6993)
* **GAT-7112:** Fix Cohort Discovery logic to correctly update an expired request to PENDING. (#1227) ([283c268](https://github.com/HDRUK/gateway-api/commit/283c26870d15ae145f00c58968dc4851bce475a2))
* **GAT-7113:** Emails masked by OpenAthens breaks workflow to user creation on RQuest (#1226) ([d8d57c9](https://github.com/HDRUK/gateway-api/commit/d8d57c952c14a9a80ad81e3c33ebb706599a73e0)), closes [GAT-7113](GAT-7113)
* **GAT-7118:** Fixes incorrect usage of dataset id, instead of versio… (#1230) ([0b2123c](https://github.com/HDRUK/gateway-api/commit/0b2123ce5d3d8aac53270d6e5b53d3934170d685))

## [2.10.0](https://github.com/HDRUK/gateway-api/compare/v2.9.1...v2.10.0) (2025-05-20)

### ✨ Features

* **GAT-4070:** Add dataProvider to tools filters. (#1219) ([cd0b01c](https://github.com/HDRUK/gateway-api/commit/cd0b01ca1c147741f94d74f8199fc5f86bd8cf2e)), closes [GAT-4070](GAT-4070)
* **GAT-4070:** Add team name to dataProvider field in tool indexing (#1220) ([8c77490](https://github.com/HDRUK/gateway-api/commit/8c7749041f51709a13b9a1cd6120250d31717fe8)), closes [GAT-4070](GAT-4070)
* **GAT-6422:** Search on the dataUses is returning - There has been a server error cURL error 28 ...  (#1222) ([1b31f44](https://github.com/HDRUK/gateway-api/commit/1b31f444374105015d0ff3245cadf31901489928)), closes [GAT-6422](GAT-6422)
* **GAT-6925:** Enable the use of aliases for Data Custodian names (#1236) ([e6d6ff2](https://github.com/HDRUK/gateway-api/commit/e6d6ff28747f9ac686494662ce573b4e9d7e7940)), closes [GAT-6925](GAT-6925)
* **GAT-6993:** Split applications out after submission (#1221) ([807078f](https://github.com/HDRUK/gateway-api/commit/807078f1e029b512a3cf24bfac9c02035263defb)), closes [GAT-6993](GAT-6993)

### 🐛 Bug Fixes

* **GAT-5972:** update v2 tools put and patch (#1232) ([82befc7](https://github.com/HDRUK/gateway-api/commit/82befc79994de9c6efb473f3b285b9e6465846e4)), closes [GAT-5972](GAT-5972)
* **GAT-6909:** Create separate enquiries for each team (#1197) ([6f8a56e](https://github.com/HDRUK/gateway-api/commit/6f8a56e92cbb606cfa6bcfbca95fd7d61d323890)), closes [GAT-6909](GAT-6909)
* **GAT-6946:** Unable to view any Datasets & BioSamples - investigate Github issue (#1231) ([089f84e](https://github.com/HDRUK/gateway-api/commit/089f84eff5781885e1d22bf1ffc41a1bfdf475e7)), closes [GAT-6946](GAT-6946)
* **GAT-6962:** Data Collections / Networks tab slow (#1216) ([f028148](https://github.com/HDRUK/gateway-api/commit/f02814821da7dd82496437b17ee60cec8c93ff0f)), closes [GAT-6962](GAT-6962)
* **GAT-6982:** Filter DAR dashboard based on first review, not latest (#1235) ([1af137c](https://github.com/HDRUK/gateway-api/commit/1af137c57bdb5bb22de4c6e61384ded146d8f840)), closes [GAT-6982](GAT-6982)
* **GAT-6985:** Getting 404 errors when clicking on derived from linked datasets (#1225) ([9b32e57](https://github.com/HDRUK/gateway-api/commit/9b32e575b21e7f69ce246345c90244a04c94f91e)), closes [GAT-6985](GAT-6985)
* **GAT-6986:** Unable to upload data uses - London SDE (#1215) ([3e36fce](https://github.com/HDRUK/gateway-api/commit/3e36fce4c4a90e7dd7f4b057057dfab6769ccc63)), closes [GAT-6986](GAT-6986)
* **GAT-6993:** For researcher add related applications to list (#1234) ([5b828e0](https://github.com/HDRUK/gateway-api/commit/5b828e0cc96ef3207f1c21156e3b424b42411399)), closes [GAT-6993](GAT-6993)

## [2.9.1](https://github.com/HDRUK/gateway-api/compare/v2.9.0...v2.9.1) (2025-05-13)

### 🐛 Bug Fixes

* **GAT-7112:** Fix Cohort Discovery logic to correctly update an expired request to PENDING. (#1227) ([356c37d](https://github.com/HDRUK/gateway-api/commit/356c37dd47d1421b2352c67e6d17aa99eeac4e25))
* **GAT-7113:** Emails masked by OpenAthens breaks workflow to user creation on RQuest (#1226) ([1abff03](https://github.com/HDRUK/gateway-api/commit/1abff03dad2315ee927737ebbccd9da0655c38fe)), closes [GAT-7113](GAT-7113)
* **GAT-7118:** Fixes incorrect usage of dataset id, instead of versio… (#1230) ([9fe420a](https://github.com/HDRUK/gateway-api/commit/9fe420aa752b992fec26768f516752b0370804fd))

## [2.9.0](https://github.com/HDRUK/gateway-api/compare/v2.8.1...v2.9.0) (2025-05-06)

### ✨ Features

* **GAT-6458:** Allow DTA to Sign in (#1190) ([0ff24b0](https://github.com/HDRUK/gateway-api/commit/0ff24b0633fa636a5f548fa2394089c956cf9c9b)), closes [GAT-6458](GAT-6458)
* **GAT-6759:** Added created_at and updated_at in enquiry_threads (#1205) ([aa33a40](https://github.com/HDRUK/gateway-api/commit/aa33a40951c7b4d6d729a5bb6d3d648f49a3ef55)), closes [GAT-6759](GAT-6759)
* **GAT-6959:** update data provider network serp with teams (#1203) ([775d0d4](https://github.com/HDRUK/gateway-api/commit/775d0d426aee43158965b5905d0191c2923189d6)), closes [GAT-6959](GAT-6959)

### 🐛 Bug Fixes

* **GAT-6599:** Allow DTA sign in from sub (#1209) ([3b66370](https://github.com/HDRUK/gateway-api/commit/3b663708f2eba9b0ed7af1327c5c0a012b2b0d05)), closes [GAT-6599](GAT-6599)
* **GAT-6678:** Bug Data uses are able to upload with missing mandatory information (#1207) ([5ae6599](https://github.com/HDRUK/gateway-api/commit/5ae6599f6893765e535052c7b7272a5a746708e6)), closes [GAT-6678](GAT-6678)
* **GAT-6726:** update question archiving (#1202) ([7db3b4e](https://github.com/HDRUK/gateway-api/commit/7db3b4e03878c2cdf9e8cedcda50cb71ad17b100)), closes [GAT-6726](GAT-6726)
* **GAT-6758:** [BE] Data Custodian Search UI (#1199) ([e27b0bb](https://github.com/HDRUK/gateway-api/commit/e27b0bb5ec454128583c7defca68bd8584feeb4d)), closes [GAT-6758](GAT-6758)
* **GAT-6854:** Extend linkage extraction job (under apps/jobs) for tools - Its not rendering on the Tools Search UI (#1210) ([f75564c](https://github.com/HDRUK/gateway-api/commit/f75564c151ba6f560bfff605edfb249a6ec65860)), closes [GAT-6854](GAT-6854)
* **GAT-6868:** Fix deletion of datasets from Collections (#1206) ([ee22f5a](https://github.com/HDRUK/gateway-api/commit/ee22f5a8349d53db8bdaa24bbf44e84afbdefada))
* **GAT-6869:** Data Custodian filter on Collections/Networks search UI has blank option that doesn't function (#1204) ([428272e](https://github.com/HDRUK/gateway-api/commit/428272e147e6e35f75f33ed1a91e80e0b81d3a5f)), closes [GAT-6869](GAT-6869)
* **GAT-6913:** Update cohort expiry email job to run on certain days, and fix email button links (#1208) ([3e33ea5](https://github.com/HDRUK/gateway-api/commit/3e33ea57792a5b2755fb4646ffa9e5a335fee5f1))
* **GAT-6941:** Show approved/approved comments in dashboards (#1198) ([3a5d71d](https://github.com/HDRUK/gateway-api/commit/3a5d71dfa67e857272d8a6c2d9c689f5bfa0f0d1)), closes [GAT-6941](GAT-6941)
* **GAT-6945:** Linked datasets field is missing in the Dur upload (#1200) ([d7db898](https://github.com/HDRUK/gateway-api/commit/d7db898b192861234dc658be089d67efe767d690)), closes [GAT-6945](GAT-6945)
* **GAT-6984:** add message to review when status set to draft by custodian (#1213) ([1f7f2a3](https://github.com/HDRUK/gateway-api/commit/1f7f2a376a00b035221c6f3c9e6938efb45ebc36)), closes [GAT-6984](GAT-6984)
* **GAT-7004:** Update cohort renewal reminder email to remove button (#1211) ([15fbb17](https://github.com/HDRUK/gateway-api/commit/15fbb17f61afcd3c6a972c49dbf250927bdcc0c7)), closes [GAT-7004](GAT-7004)

## [2.8.1](https://github.com/HDRUK/gateway-api/compare/v2.8.0...v2.8.1) (2025-04-30)

### 🐛 Bug Fixes

* **GAT-6913:** Update cohort expiry email job to run on certain days, and fix email button links (#1208) ([db19f25](https://github.com/HDRUK/gateway-api/commit/db19f257a449619f65ee97b56c45b535e781dbc5))
* **GAT-7004:** Update cohort renewal reminder email to remove button (#1211) ([2f12883](https://github.com/HDRUK/gateway-api/commit/2f12883b1fcd8cb648e0a7371d310829182a27e2)), closes [GAT-7004](GAT-7004)

## [2.8.0](https://github.com/HDRUK/gateway-api/compare/v2.7.0...v2.8.0) (2025-04-22)

### ✨ Features

* **GAT-6005:** Revert - add sde concierge and redirect enquiries (#1165) (#1182) ([0cbb225](https://github.com/HDRUK/gateway-api/commit/0cbb2257a4f5ad6059f7c1bb37770455fe41b7c4)), closes [GAT-6005](GAT-6005)
* **GAT-6395:** Add isCohortDiscovery dataset filter (#1193) ([f9762c8](https://github.com/HDRUK/gateway-api/commit/f9762c882f915a0b16581f95032b6db8c0c1f1a0)), closes [GAT-6395](GAT-6395)
* **GAT-6657:** update DAR file deleting to delete associated answer (#1191) ([422c22c](https://github.com/HDRUK/gateway-api/commit/422c22cb8f651d1fe31607ea61af1cf50df1ec77)), closes [GAT-6657](GAT-6657)
* **GAT-6682:** Add descriptions to subsections in seeder (#1194) ([34761ff](https://github.com/HDRUK/gateway-api/commit/34761ff725cc30d7cc98a91b3deed392776820e3)), closes [GAT-6682](GAT-6682)
* **GAT-6682:** add missing questions and sections to question bank (#1185) ([31923ae](https://github.com/HDRUK/gateway-api/commit/31923ae3533e055ce68f732b377499757b074190)), closes [GAT-6682](GAT-6682)
* **GAT-6695:** Enable user to push application back to draft (#1188) ([4177888](https://github.com/HDRUK/gateway-api/commit/41778884d6ea703f5a5731835784a50c0a750507)), closes [GAT-6695](GAT-6695)

### 🐛 Bug Fixes

* **GAT-6656:** fix action required counting logic (#1189) ([5046d20](https://github.com/HDRUK/gateway-api/commit/5046d20ea131f8cbaf1ab2fbcea159922e22b28d))
* **GAT-6672:** update patch to handle nested questions (#1181) ([5ce74cd](https://github.com/HDRUK/gateway-api/commit/5ce74cd245c6c2afd73dfd16b9a7ced6aa8fb3a7)), closes [GAT-6672](GAT-6672)
* **GAT-6870:** add dar template count endpoints (#1186) ([4236709](https://github.com/HDRUK/gateway-api/commit/4236709026216775870251d330e560c30945c82d)), closes [GAT-6870](GAT-6870)

## [2.7.0](https://github.com/HDRUK/gateway-api/compare/v2.6.0...v2.7.0) (2025-04-09)

### ✨ Features

* **GAT-6005:** Revert - add sde concierge and redirect enquiries (#1165) (#1182) ([d527d14](https://github.com/HDRUK/gateway-api/commit/d527d14d947f5a39671c796231ec0cddab7c39b6)), closes [GAT-6005](GAT-6005)

## [2.6.0](https://github.com/HDRUK/gateway-api/compare/v2.5.0...v2.6.0) (2025-04-08)

### ✨ Features

* **GAT-1234:** Revert the reverted changes from main ([dbb3e3e](https://github.com/HDRUK/gateway-api/commit/dbb3e3e8047c4c8073a26f13cf229a484034b693)), closes [GAT-1234](GAT-1234) [GAT-1234](GAT-1234)

## [2.5.0](https://github.com/HDRUK/gateway-api/compare/v2.4.1...v2.5.0) (2025-04-07)

### ✨ Features

* **GAT-5446:** Unit testing for CRUD permission error for Analysis Scripts & Software (#1170) ([83072f9](https://github.com/HDRUK/gateway-api/commit/83072f91e4ee84523250d3f3811919286b066a2a)), closes [GAT-5446](GAT-5446)
* **GAT-5639:** related entities deleted when collection archived (#1163) ([987f77a](https://github.com/HDRUK/gateway-api/commit/987f77aeb0776c07d2097b955173086a9cf12219)), closes [GAT-5639](GAT-5639)
* **GAT-5763:** Limit Collections on Data Custodian page to those created by the DataCustodian (#1155) ([f7c6864](https://github.com/HDRUK/gateway-api/commit/f7c686478f08b5c9c86830a40275909b233d4de5)), closes [GAT-5763](GAT-5763)
* **GAT-6414:** Extend linkage extraction job (under apps/jobs) for tools and publications in Dataset & BioSample metadata (#1166) ([0b80bb4](https://github.com/HDRUK/gateway-api/commit/0b80bb4d8243c7ac22071f138fcb5e3711cd89af)), closes [GAT-6414](GAT-6414)
* **GAT-6607:** Count and filtering on dar templates (#1152) ([550617a](https://github.com/HDRUK/gateway-api/commit/550617a6afb962a8175e501c3a1ddfbfbee6cb24)), closes [GAT-6607](GAT-6607)
* **GAT-6612:** endpoint for user to delete a DAR (#1159) ([18f9d73](https://github.com/HDRUK/gateway-api/commit/18f9d7378faf8e430deeb45d032e808d9aa65af7)), closes [GAT-6612](GAT-6612)
* **GAT-6713:** Add Access Cohort Discovery to Library endpoint (#1172) ([c2b0456](https://github.com/HDRUK/gateway-api/commit/c2b0456b93f13b2f39f0a9dc0a5366ce38bd98e6)), closes [GAT-6713](GAT-6713)

## [2.4.1](https://github.com/HDRUK/gateway-api/compare/v2.4.0...v2.4.1) (2025-03-31)

### 🐛 Bug Fixes

* **GAT-6665:** Fix answer fetching and section matching in primary applicant info ([126c794](https://github.com/HDRUK/gateway-api/commit/126c7949efeca2272d678203276b2fa4851b88b3))

## [2.4.0](https://github.com/HDRUK/gateway-api/compare/v2.3.3...v2.4.0) (2025-03-25)

### ✨ Features

* **GAT-6391:** add file based dar templates and files for dar reviews (#1144) ([bb66a88](https://github.com/HDRUK/gateway-api/commit/bb66a88328317aa24fdd9683a0423fdfe4c89d7a)), closes [GAT-6391](GAT-6391)
* **GAT-6396:** Add new filter to Datasets table for 'Data Standard' (#1145) ([5cb05c7](https://github.com/HDRUK/gateway-api/commit/5cb05c78c2165f86c78c2a42fd7ccca0afbf8a3a)), closes [GAT-6396](GAT-6396)
* **GAT-6415:** Create a new job initiator to create a publication from Dataset & BioSample metadata (#1135) ([dcd9d94](https://github.com/HDRUK/gateway-api/commit/dcd9d94ae6c2f71e6fa0e35a6d1eeb41751a1b96)), closes [GAT-6415](GAT-6415)
* **GAT-6458:** - Split out social logins For DTA (#1112) ([1247458](https://github.com/HDRUK/gateway-api/commit/1247458b9810d2b9f9e761c52a806fd507802960)), closes [GAT-6458](GAT-6458)
* **GAT-6528:** add dar count endpoints with all counts (#1139) ([8d690fa](https://github.com/HDRUK/gateway-api/commit/8d690fae48a542d4d2799014172b9989a7949016)), closes [GAT-6528](GAT-6528)

### 🐛 Bug Fixes

* **GAT-6415:** update dataset versions and publications (#1143) ([250c9c6](https://github.com/HDRUK/gateway-api/commit/250c9c67b14770803408deadbb8a1ce338ef1e5d)), closes [GAT-6415](GAT-6415)
* **GAT-6430:** check for array in validations (#1137) ([e750dcc](https://github.com/HDRUK/gateway-api/commit/e750dcc9bee3ec7ec5555a35a6723383445ef985)), closes [GAT-6430](GAT-6430)
* **GAT-6430:** patch to update question_json fields (#1134) ([6419e74](https://github.com/HDRUK/gateway-api/commit/6419e74d80d664d729725b755bd7a229d652ae66)), closes [GAT-6430](GAT-6430)
* **GAT-6443:** add short title column in dataset_versions for search (#1149) ([1723980](https://github.com/HDRUK/gateway-api/commit/1723980f86a20cc3a3fe199e88e477db9905a0c0)), closes [GAT-6443](GAT-6443)
* **GAT-6450:** Data use upload not rendering correct content in editing view (#1133) ([866663f](https://github.com/HDRUK/gateway-api/commit/866663fd0c3eb877b9f565fada92a15f05c17a4c)), closes [GAT-6450](GAT-6450)
* **GAT-6531:** Data Use Register not matching Gateway Datasets & BioSamples during upload process (#1132) ([35dce0f](https://github.com/HDRUK/gateway-api/commit/35dce0f28134a79ef142d7cba8d6aae684f90950)), closes [GAT-6531](GAT-6531)
* **GAT-6617:** array access, not property (#1140) ([61031fd](https://github.com/HDRUK/gateway-api/commit/61031fd3d8c5b437f4afa32a96ab2596cb5e249a)), closes [GAT-6617](GAT-6617)
* **GAT-6630:** ease permissions on dar/sections get (#1150) ([1394066](https://github.com/HDRUK/gateway-api/commit/139406633e79b4bd5c8661c2dc97bf2621d4c1af)), closes [GAT-6630](GAT-6630)

## [2.3.3](https://github.com/HDRUK/gateway-api/compare/v2.3.2...v2.3.3) (2025-03-17)

### 🐛 Bug Fixes

* **GAT-6617:** array access, not property (#1140) ([923a0bb](https://github.com/HDRUK/gateway-api/commit/923a0bb95760cc96a178e06023ac4dfba28282f3)), closes [GAT-6617](GAT-6617)

## [2.3.2](https://github.com/HDRUK/gateway-api/compare/v2.3.1...v2.3.2) (2025-03-13)

### 🐛 Bug Fixes

* **GAT-6430:** check for array in validations (#1137) ([dadaf96](https://github.com/HDRUK/gateway-api/commit/dadaf9649363e1d65e77b13624b9dfb08c61baca)), closes [GAT-6430](GAT-6430)

## [2.3.1](https://github.com/HDRUK/gateway-api/compare/v2.3.0...v2.3.1) (2025-03-13)

### 🐛 Bug Fixes

* **GAT-6430:** patch to update question_json fields (#1134) ([0be8506](https://github.com/HDRUK/gateway-api/commit/0be850696abce7d5966e31499eebf58f4c4a969d)), closes [GAT-6430](GAT-6430)

## [2.3.0](https://github.com/HDRUK/gateway-api/compare/v2.2.0...v2.3.0) (2025-03-10)

### ✨ Features

* **GAT-439:** SOURSD integration additions for DUR link (#1110) ([0bcce27](https://github.com/HDRUK/gateway-api/commit/0bcce274118ce1df209186a973d186445a262ab1)), closes [GAT-439](GAT-439)
* **GAT-5972:** Stabilisation of Tools endpoints (#1122) ([092dd1e](https://github.com/HDRUK/gateway-api/commit/092dd1e155f40e7441655c29166b6d50b2bb18be)), closes [GAT-5972](GAT-5972)
* **GAT-5973:** Stabilisation of Publication endpoint (#1118) ([7906eca](https://github.com/HDRUK/gateway-api/commit/7906eca39477a5234a680a9abaeadba0cdc7d17f)), closes [GAT-5973](GAT-5973)
* **GAT-6184:** de-Duplication of Enquiry Notifications if multiple datasets from same custodian (#1082) ([47642ad](https://github.com/HDRUK/gateway-api/commit/47642ad35082712dfd3440774ffd65bcc11849cd)), closes [GAT-6184](GAT-6184) [GAT-6184](GAT-6184)
* **GAT-6362:** Enable DARs to have different submission/approval statuses from each custodian (#1093) ([762487a](https://github.com/HDRUK/gateway-api/commit/762487a977c3b73d8ef20c1118a79e795534297b)), closes [GAT-6362](GAT-6362)
* **GAT-6377:** User DAR dashboard (#1117) ([eff73f4](https://github.com/HDRUK/gateway-api/commit/eff73f495877d9ce9fa1c5cc9371a39b95226c23)), closes [GAT-6377](GAT-6377)
* **GAT-6417:** Observers for Elastic Indexing - Teams Update (#1114) ([691c0be](https://github.com/HDRUK/gateway-api/commit/691c0be97653353bff13403efb533b8f5cbf54df)), closes [GAT-6417](GAT-6417)
* **GAT-6448:** Notify researcher/custodian when comments made on DAR application (#1121) ([9d12ea5](https://github.com/HDRUK/gateway-api/commit/9d12ea5c10dddfe227f9ed4e1f36d7a016196ebd)), closes [GAT-6448](GAT-6448)

### 🐛 Bug Fixes

* **GAT-5751:** teams patch returns 200 (#1115) ([5770c7f](https://github.com/HDRUK/gateway-api/commit/5770c7ff50e80b0fd7ce133b4738b4200f09b837)), closes [GAT-5751](GAT-5751)
* **GAT-6215:** correct the handling of urls with spaces (#1123) ([b7adc5d](https://github.com/HDRUK/gateway-api/commit/b7adc5d2718b218c6743757fa5d11b3db7a8ac88)), closes [GAT-6215](GAT-6215)
* **GAT-6261:** validate pid in metadata (#1119) ([50aa608](https://github.com/HDRUK/gateway-api/commit/50aa6082ac0f59af18f7fe27d756ad3c8ff4462d)), closes [GAT-6261](GAT-6261) [GAT-6261](GAT-6261)
* **GAT-6287:** Use referrer for social logins - revert (#1107) ([9fd056a](https://github.com/HDRUK/gateway-api/commit/9fd056a8a2591127e975509d76f2d94c78555d6c)), closes [GAT-6287](GAT-6287)
* **GAT-6406:** - Allow minutes in federation update (#1126) ([2e23c63](https://github.com/HDRUK/gateway-api/commit/2e23c63e97e2e262c247f09a34992b2ecd8e9e4c)), closes [GAT-6406](GAT-6406) [GAT-6406](GAT-6406)

## [2.2.0](https://github.com/HDRUK/gateway-api/compare/v2.1.0...v2.2.0) (2025-02-25)

### ✨ Features

* **GAT-6216:** Update commit behaviour ([f5cdb8d](https://github.com/HDRUK/gateway-api/commit/f5cdb8ddde5f87ce22db1786a999633d1fd01118)), closes [GAT-6216](GAT-6216) [GAT-6216](GAT-6216)
* **GAT-6216:** Update commit behaviour ([5182c8c](https://github.com/HDRUK/gateway-api/commit/5182c8cc144de2a428be9d815b65d45f16670d1e)), closes [GAT-6216](GAT-6216)

## [2.1.0](https://github.com/HDRUK/gateway-api/compare/v2.0.0...v2.1.0) (2025-02-25)

### ✨ Features

* **GAT-6216:** Trigger release ([14ca3ee](https://github.com/HDRUK/gateway-api/commit/14ca3ee6cb34281e315adee8a6d07254d8433ff9)), closes [GAT-6216](GAT-6216)
* **GAT-6216:** Trigger release round 3 ([fc7d697](https://github.com/HDRUK/gateway-api/commit/fc7d6977e202d7fe8867952a0109dbff585cabb2)), closes [GAT-6216](GAT-6216) [GAT-6216](GAT-6216) [/github.com/HDRUK/gateway-api](gateway-api) [GAT-6216](GAT-6216) [GAT-6216](GAT-6216)

### 🐛 Bug Fixes

* **GAT-6216:** Add in force and rebase to avoid conflicts ([bdaca07](https://github.com/HDRUK/gateway-api/commit/bdaca07d998d618bcd0fe0c171de3e03360112e9)), closes [GAT-6216](GAT-6216)
* **GAT-6216:** Add in force and rebase to avoid conflicts (#1102) ([42c501f](https://github.com/HDRUK/gateway-api/commit/42c501f78c5d2465711fb469a8e1292f444d5388)), closes [GAT-6216](GAT-6216)
* **GAT-6287:** Use referrer for social logins - revert (#1107) ([ebbe11b](https://github.com/HDRUK/gateway-api/commit/ebbe11b323cca456dd13f66324e5f0a5c02e63e8)), closes [GAT-6287](GAT-6287)
* **GAT-6287:** Use referrer for social logins (#1097) ([c45cbae](https://github.com/HDRUK/gateway-api/commit/c45cbae261ebc1ff81a287c8e40ed34bdbf2af74)), closes [GAT-6287](GAT-6287)

## [0.14.0](https://github.com/HDRUK/gateway-api/compare/v0.13.0...v0.14.0) (2025-02-21)

### ✨ Features

* **GAT-6216:** Trigger release round 4 ([b4553a3](https://github.com/HDRUK/gateway-api/commit/b4553a3dfc1788a7add5bd7ff249461f6fe07324)), closes [GAT-6216](GAT-6216) [GAT-6216](GAT-6216) [GAT-6216](GAT-6216)

### 🐛 Bug Fixes

* **GAT-6216:** Add in force and rebase to avoid conflicts (#1104) ([9c896ac](https://github.com/HDRUK/gateway-api/commit/9c896ac9f517231504dba45cf93776571ec5057a)), closes [GAT-6216](GAT-6216)

## [0.13.0](https://github.com/HDRUK/gateway-api/compare/v0.12.0...v0.13.0) (2025-02-21)

### ✨ Features

* **GAT-6216:** Trigger release round 3 ([6129007](https://github.com/HDRUK/gateway-api/commit/6129007e4823b634ffd122cff090c015a0e049f8)), closes [GAT-6216](GAT-6216)

## [0.12.0](https://github.com/HDRUK/gateway-api/compare/v0.11.1...v0.12.0) (2025-02-21)

### ✨ Features

* **GAT-6216:** Trigger release ([0045465](https://github.com/HDRUK/gateway-api/commit/0045465ca819d6a499fe7ab796ff88129b8b88e0)), closes [GAT-6216](GAT-6216)

## [0.11.1](https://github.com/HDRUK/gateway-api/compare/v0.11.0...v0.11.1) (2025-02-20)

## [0.11.0](https://github.com/HDRUK/gateway-api/compare/v0.10.0...v0.11.0) (2025-02-20)

### ✨ Features

* **GAT-5909:** Set up teams/dars endpoint for custodian dashboard (#1083) ([19b53bb](https://github.com/HDRUK/gateway-api/commit/19b53bb0f579402262184bc266a6c62ae0ac9c3b)), closes [GAT-5909](GAT-5909)
* **GAT-6183:** Updated rejection email (#1065) ([2ccc45d](https://github.com/HDRUK/gateway-api/commit/2ccc45d84613912326af42460fb24cb05665146f)), closes [GAT-6183](GAT-6183) [GAT-6183](GAT-6183) [app/Console/Commands/UpdateEmailCohortButtonsGat6183](Gat6183)
* **GAT-6250:** Add file upload question type support (#1066) ([a1dbc32](https://github.com/HDRUK/gateway-api/commit/a1dbc32523b03daa3d8689af0782632f9e900a59)), closes [GAT-6250](GAT-6250)
* **GAT-6346:** Update formatting of dar guidance text (#1081) ([2164448](https://github.com/HDRUK/gateway-api/commit/2164448ef94ebed3ed25f318cbfb3e1baa02adf3)), closes [GAT-6346](GAT-6346)
* **GAT-6363:** DAR reviews, add comments table, and update endpoints (#1077) ([212489f](https://github.com/HDRUK/gateway-api/commit/212489f362f6eebb1c157ed04c6bdc2d9a969df2)), closes [GAT-6363](GAT-6363)

### 🐛 Bug Fixes

* **GAT-5624:** Decode tool description and results_insights (#1067) ([9b86472](https://github.com/HDRUK/gateway-api/commit/9b864726bfe6fcd582cc19cf73a0b4f27c017d2d)), closes [GAT-5624](GAT-5624)
* **GAT-6182:** Fix for cohort admin download display error (#1072) ([96a035f](https://github.com/HDRUK/gateway-api/commit/96a035fa13dbfddaa71cfa8e5c8e0241983d747f))
* **GAT-6386:** Getting GMI working (#1074) ([9ff7450](https://github.com/HDRUK/gateway-api/commit/9ff745002af6a34f1b147cf564c612a2a9e99375)), closes [GAT-6386](GAT-6386)

## [0.10.0](https://github.com/HDRUK/gateway-api/compare/v0.9.0...v0.10.0) (2025-02-20)

### ✨ Features

* **GAT-6320:** Fix the merge conflict (#1087) ([43756d2](https://github.com/HDRUK/gateway-api/commit/43756d223061a489e3c34082aec683bbb7add7a8))

## [0.9.0](https://github.com/HDRUK/gateway-api/compare/v0.8.0...v0.9.0) (2025-02-20)

### ✨ Features

* (GAT-5697) - return publisher team id to frontend if exists (#950) ([ced7d76](https://github.com/HDRUK/gateway-api/commit/ced7d76edf7f6d397b0e7ba867a5841018812484)), closes [GAT-5697](GAT-5697)
* **GAT-4618:**  Customer Satisfaction Controller (#1049) ([05a8c22](https://github.com/HDRUK/gateway-api/commit/05a8c2282051ed09bf042f9401b919b985acbf09)), closes [GAT-4618](GAT-4618) [GAT-4618](GAT-4618)
* **GAT-5927:** send email notification on custodian status update (#1062) ([5374308](https://github.com/HDRUK/gateway-api/commit/5374308942faae91c9a426496c15ad99e948a0bb)), closes [GAT-5927](GAT-5927)
* **GAT-61-82:** Added in additional data to export (#1059) ([a3904ff](https://github.com/HDRUK/gateway-api/commit/a3904fff407acd14e6d0b1dfcf638665f9cdd1a1)), closes [GAT-61-82](GAT-61-82) [GAT-61-82](GAT-61-82)
* **GAT-6181:** Added in sector to cohort response (#1058) ([6cc5d36](https://github.com/HDRUK/gateway-api/commit/6cc5d364fdec6ac0166d7112ff27cb7ea8609edd)), closes [GAT-6181](GAT-6181)
* **GAT-6223:** Update endpoints in data access module to avoid duplication (#1052) ([428b5a8](https://github.com/HDRUK/gateway-api/commit/428b5a86913afabecb1279e0625a0e1ee62d4d27)), closes [GAT-6223](GAT-6223)
* **GAT-6224:** Add permissions to dar/applications endpoints (#1055) ([b7c4577](https://github.com/HDRUK/gateway-api/commit/b7c457731056303d74f86c2829a6e0dbd5c42e47)), closes [GAT-6224](GAT-6224)

### 🐛 Bug Fixes

* (GAT-5587) insight being skipped for script (#959) ([c86a749](https://github.com/HDRUK/gateway-api/commit/c86a749987bdb96976c1093ece2b4e586c168b16)), closes [GAT-5587](GAT-5587)
* (GAT-5762) - only show active, durs, collections & pubs (#941) ([5540c7c](https://github.com/HDRUK/gateway-api/commit/5540c7c5b8caefc89b32382451775479fa761b0c)), closes [GAT-5762](GAT-5762) [GAT-5762](GAT-5762)
* (GAT-5866) only show active publications (#1021) ([97a2979](https://github.com/HDRUK/gateway-api/commit/97a297990def2c811793aa5db08c2d4320093f00)), closes [GAT-5866](GAT-5866) [GAT-5866](GAT-5866)
* (GAT-5907) - Add in middleware to fed endpoints (#983) ([14ff410](https://github.com/HDRUK/gateway-api/commit/14ff410c5b1ddacb4be1b58680c43915c0bf491b)), closes [GAT-5907](GAT-5907) [GAT-5907](GAT-5907)
* (GAT-5907) missing GMI (#974) ([08239bc](https://github.com/HDRUK/gateway-api/commit/08239bc58e59514783a715b45330fa7967498c1d)), closes [GAT-5907](GAT-5907)
* (GAT-5945-4) - format titles on collections post v2 (#1032) ([e32a129](https://github.com/HDRUK/gateway-api/commit/e32a129c98ab2784bd621345f51c3b65e288e942)), closes [GAT-5945-4](GAT-5945-4)
* (GAT-5945) - encode name if exists (#990) ([d38bcf0](https://github.com/HDRUK/gateway-api/commit/d38bcf039f193d7d94cbd8886f446f7e9218cdbe)), closes [GAT-5945](GAT-5945) [GAT-5945](GAT-5945)
* (GAT-5946) - Format name for edits on collections, teams and tools (#1017) ([b8d0d4f](https://github.com/HDRUK/gateway-api/commit/b8d0d4f718cf682e0e7b287679f7c8b5f3650957)), closes [GAT-5946](GAT-5946)
* (GAT-5992) - gatewayid not showing link on frontend (#999) ([7340614](https://github.com/HDRUK/gateway-api/commit/734061429ca4cdd132b1d11c27a35c3eb770cf56)), closes [GAT-5992](GAT-5992) [GAT-5992](GAT-5992)
* (GAT-6108) - remove all senstive data from users and teams (#1034) ([69dce99](https://github.com/HDRUK/gateway-api/commit/69dce998ca9d0cf2f3f87c8bb06da0c1607b84e6)), closes [GAT-6108](GAT-6108)
* bad url ([0e7c954](https://github.com/HDRUK/gateway-api/commit/0e7c95444e1f21fa5d6b442987024db2e9a4262e))
* **GAT-6215:** Bug - Collection logo not loading on search UI (#1050) ([0a6e158](https://github.com/HDRUK/gateway-api/commit/0a6e158249b0454ff7e91c515e772a0798bd59a9)), closes [GAT-6215](GAT-6215)
* **GAT-6223:** check if team id is numeric (#1063) ([3317dde](https://github.com/HDRUK/gateway-api/commit/3317dde410f55ce6e38d4d3d9adb7f9ee2d0416d)), closes [GAT-6223](GAT-6223)
* **GAT-6386:** Getting GMI working (#1074) ([768fe8e](https://github.com/HDRUK/gateway-api/commit/768fe8e39e556324b8313d2a444a315ec1de244c)), closes [GAT-6386](GAT-6386)
* Gracefully handle an empty result from epmc. (#1061) ([b25e9c6](https://github.com/HDRUK/gateway-api/commit/b25e9c6d0cae5f72e5fd7d03dd3cede434e199ff))
