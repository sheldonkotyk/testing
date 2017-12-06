<?php namespace App\Controllers\Admin;
 
    use Auth;
use BaseController;
use Form;
use Input;
use Redirect;
use Sentry;
use View;
use Redis;
use Session;
use Client;
use App;
use Config;
use Carbon;
use Donation;
use Cache;
use Queue;
use URL;
 
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    

    public function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }

    public function index()
    {

        $client = new Client;
        $client_id = Session::get('client_id');

        $data['reloaded']= Cache::get('dashboard_reload-'.$client_id);

        if ($data['reloaded']=='Reloading Stats... Please Wait.'&&empty(Cache::get('dashboard-'.$client_id))) {
            $data['org']=Client::find($client_id);
            return view('admin.views.index', $data);
        }

        if (empty(Cache::get('dashboard-'.$client_id))) {
            Cache::put('dashboard_reload-'.$client_id, 'Reloading Stats... Please Wait.', 5);
            $data = [
            'client_id'=>$client_id];
            Queue::push('reloadDashboardStats', $data);
                
            $data['org']=Client::find($client_id);
            return view('admin.views.index', $data);
        }

        $data = $client->load_stats($client_id);
        $data['reloaded']= Cache::get('dashboard_reload-'.$client_id);

        if ($data['reloaded']=='done') {
            Cache::forget('dashboard_reload-'.$client_id);
            $data['reloaded']='';
            Session::flash('message', 'Dashboard Stats successfully reloaded');
            Session::flash('alert', 'success');
        }

        return view('admin.views.index', $data);
    }
    public function upgrades()
    {
        return view('admin.views.upgrades');
    }
        
    public function reloadStats()
    {
        $client_id= Session::get('client_id');

        Cache::put('dashboard_reload-'.$client_id, 'Reloading Stats... Please Wait.', '5');

        $data = [
            'client_id'=>$client_id];

        Queue::push('reloadDashboardStats', $data);

        return redirect('admin')->with('message', 'The Dashboard Stats have been queued for reload. This may take some time. <a href="'.URL::to('admin').'">Refresh this page</a> to see if the reload has finished.')->with('alert', 'success');
    }
}
