<?php

namespace GrantHolle\PowerSchool\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use OpenID_RelyingParty;
use OpenID_Extension_AX;
use OpenID_Extension;
use OpenID_Message;
use Net_URL2;
use Spatie\Url\Url;

trait AuthenticatesPowerSchool
{
    /**
     * Receives the SSO request and requests data from PS
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \OpenID_Exception
     */
    public function authenticate(Request $request)
    {
        // PS usernames don't have to be "normal"
        // and will throw an error if they are used
        // decoded by Laravel, so we need to re-encode
        // the last segment of the url, which is the username
        $decodedIdentifier = $request->input('openid_identifier');
        $url = Url::fromString($decodedIdentifier);
        $username = $url->getLastSegment();
        $identifier = str_replace($username, rawurlencode($username), $decodedIdentifier);

        // Set up the relying party
        $relyingParty = new OpenID_RelyingParty(route('sso.verify'), url('/'), $identifier);
        $relyingParty->disableAssociations();
        $authRequest = $relyingParty->prepare();

        // Add all the extension fields for the request
        $ax = new OpenID_Extension_AX(OpenID_Extension::REQUEST);

        $ax->set('type.studentids', 'http://powerschool.com/entity/studentids');
        $ax->set('type.dcid', 'http://powerschool.com/entity/id');
        $ax->set('type.usertype', 'http://powerschool.com/entity/type');
        $ax->set('type.ref', 'http://powerschool.com/entity/ref');
        $ax->set('type.email', 'http://powerschool.com/entity/email');
        $ax->set('type.firstName', 'http://powerschool.com/entity/firstName');
        $ax->set('type.lastName', 'http://powerschool.com/entity/lastName');
        $ax->set('type.districtName', 'http://powerschool.com/entity/districtName');
        $ax->set('type.districtCustomerNumber', 'http://powerschool.com/entity/districtCustomerNumber');
        $ax->set('type.districtState', 'http://powerschool.com/entity/districtState');
        $ax->set('type.districtCountry', 'http://powerschool.com/entity/districtCountry');
        $ax->set('type.schoolID', 'http://powerschool.com/entity/schoolID');
        $ax->set('type.usersDCID', 'http://powerschool.com/entity/usersDCID');
        $ax->set('type.teacherNumber', 'http://powerschool.com/entity/teacherNumber');
        $ax->set('type.adminSchools', 'http://powerschool.com/entity/adminSchools');
        $ax->set('type.teacherSchools', 'http://powerschool.com/entity/teacherSchools');
        $ax->set('mode', 'fetch_request');
        $ax->set('required', 'studentids,dcid,usertype,ref,email,firstName,lastName,districtName,districtCustomerNumber,districtState,districtCountry,schoolID,usersDCID,teacherNumber,adminSchools,teacherSchools');

        $authRequest->addExtension($ax);

        return redirect($authRequest->getAuthorizeURL());
    }

    /**
     * Receives the data after successful authentication
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $relyingParty = new OpenID_RelyingParty(route('sso.verify'), $request->getSchemeAndHttpHost(), $request->input('openid_identity'));
        $relyingParty->disableAssociations();
        $server = $request->server();

        $message = new OpenID_Message($server['QUERY_STRING'], OpenID_Message::FORMAT_HTTP);
        $result = $relyingParty->verify(new Net_URL2(route('sso.verify') . '?' . $server['QUERY_STRING']), $message);

        if (!$result->success()) {
            return response()->json([], 403);
        }

        // Create the user record if it doesn't exist
        $user = app(config('powerschool.user-model'))->firstOrCreate([
            $this->identifier() => $request->input('openid_identity'),
        ]);

        $this->guard()->login($user);

        return $this->sendLoginResponse($request, collect($request->all()));
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Collection  $data
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request, Collection $data)
    {
        $request->session()->regenerate();

        return $this->authenticated($request, $this->guard()->user(), $data)
            ?: redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @param  \Illuminate\Support\Collection  $data
     * @return mixed
     */
    protected function authenticated(Request $request, $user, Collection $data)
    {
        //
    }

    /**
     * Get the open identifier column for the users.
     *
     * @return string
     */
    public function identifier()
    {
        return 'openid_identifier';
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

     /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}
