import { spawn, execFile } from 'node:child_process';
import { appendFileSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const [root, backend, php, viteEntry, runtime, logs] = process.argv.slice(2).map(value => resolve(value));
const services = [
  { name: 'backend', file: php, args: ['-S', '127.0.0.1:8110', '-t', 'public', 'router.php'], cwd: backend },
  { name: 'frontend', file: process.execPath, args: [viteEntry, 'dev', '--host', '127.0.0.1', '--port', '5174', '--strictPort'], cwd: resolve(root, 'player') }
];
mkdirSync(logs, { recursive: true });
for (const s of services) { s.log=resolve(logs,`${s.name}-development.log`);s.errorLog=resolve(logs,`${s.name}-development.error.log`);writeFileSync(s.log,'');writeFileSync(s.errorLog,''); }
const children=new Map();let stopping=false;let fatal=null;
function emit(s,stream,text){if(!text)return;appendFileSync(stream==='stderr'?s.errorLog:s.log,text+'\n');process.stdout.write(`[${s.name}] ${text}\n`);}
function pidFile(s){return resolve(runtime,`${s.name}-development.pid`)}
function killTree(p){if(!p||p.exitCode!==null)return;if(process.platform==='win32')execFile('taskkill',['/PID',String(p.pid),'/T','/F'],()=>{});else p.kill('SIGTERM')}
async function healthy(url){try{return(await fetch(url)).status===200}catch{return false}}
async function cleanup(){if(stopping)return;stopping=true;for(const p of children.values())killTree(p);await new Promise(r=>setTimeout(r,500));for(const s of services)rmSync(pidFile(s),{force:true})}
function fail(message){if(!fatal){fatal=new Error(message);process.stderr.write(`[supervisor] ${message}\n`)} }
for (const signal of ['SIGINT','SIGTERM']) process.on(signal, async () => { if (!stopping) { fatal = fatal ?? new Error(`Received ${signal}.`); await cleanup(); process.exitCode = 130; } });
for(const s of services){const env={...process.env};if(s.name==='frontend')env.CI='true';const p=spawn(s.file,s.args,{cwd:s.cwd,env,windowsHide:true,stdio:['inherit','pipe','pipe']});children.set(s.name,p);writeFileSync(pidFile(s),String(p.pid));p.stdout.setEncoding('utf8');p.stderr.setEncoding('utf8');p.stdout.on('data',d=>d.split(/\r?\n/).forEach(x=>emit(s,'stdout',x)));p.stderr.on('data',d=>d.split(/\r?\n/).forEach(x=>emit(s,'stderr',x)));p.on('error',e=>fail(`${s.name} could not start: ${e.message}`));p.on('exit',(code,signal)=>{if(!stopping&&code!==0)fail(`${s.name} exited with code ${code??'null'}${signal?` (${signal})`:''}.`)})}
for(let i=0;i<60&&!fatal;i++){if([...children.values()].some(p=>p.exitCode!==null)){fail('A development service exited before health checks passed.');break}if(await healthy('http://127.0.0.1:8110/api/v1/health')&&await healthy('http://127.0.0.1:5174/'))break;await new Promise(r=>setTimeout(r,500));if(i===59)fail('Development services did not become healthy within 30 seconds.')}
if(!fatal){process.stdout.write('[portal] Portal: http://127.0.0.1:5174/\n[portal] Login: http://127.0.0.1:5174/login\n[portal] Backend API: http://127.0.0.1:8110/api/v1/health\n');await new Promise(done=>{const check=()=>{if(fatal||[...children.values()].some(p=>p.exitCode!==null)){if(!fatal)fail('A development service exited unexpectedly.');done()}else setTimeout(check,250)};check()})}
await cleanup();process.exitCode=fatal?1:0;
