import {runCLI} from '@wp-playground/cli';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const blueprint=JSON.parse(fs.readFileSync(path.join(root,'tests/blueprint.json'),'utf8'));
const site=await runCLI({command:'server',wp:'latest',php:'8.3',port:9400,workers:2,blueprint,quiet:true,mount:[{hostPath:path.join(root,'maeda-perfis'),vfsPath:'/wordpress/wp-content/plugins/maeda-perfis'},{hostPath:path.join(root,'tests'),vfsPath:'/wordpress/pmp-tests'},{hostPath:path.join(root,'tests/mu'),vfsPath:'/wordpress/wp-content/mu-plugins'}]});
try {
 const boot=await(await fetch(site.serverUrl+'/pmp-tests/bootstrap.php')).json();
 if(boot.error)throw new Error(boot.error);
 const result=await(await fetch(site.serverUrl+'/pmp-tests/integration.php')).json();
 for(const item of result.checks || [])console.log((item.passed?'PASS ':'FAIL ')+item.name);
 console.log(JSON.stringify({success:result.success,checks:result.checks?.length,versions:result.versions,error:result.error,db_error:result.db_error}));
 if(!result.success)process.exitCode=1;
 if(result.success) {
  const pages=await(await fetch(site.serverUrl+'/pmp-tests/site-integration.php')).json();
  for(const item of pages.checks || [])console.log((item.passed?'PASS ':'FAIL ')+item.name);
  console.log(JSON.stringify({success:pages.success,site_checks:pages.checks?.length,error:pages.error}));
  if(!pages.success)process.exitCode=1;
  if(pages.success){const account=await(await fetch(site.serverUrl+'/pmp-tests/account-integration.php')).json();for(const item of account.checks || [])console.log((item.passed?'PASS ':'FAIL ')+item.name);console.log(JSON.stringify({success:account.success,account_checks:account.checks?.length,error:account.error}));if(!account.success)process.exitCode=1;}
 }
}finally{await site[Symbol.asyncDispose]();}
