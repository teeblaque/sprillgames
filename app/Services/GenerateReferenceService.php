<?php

namespace App\Services;

class GenerateReferenceService
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateReference()
    {
        $getReference = 'sprillgames'.uniqid().time();
        return $getReference;
    }

    public function getReferenceShort(){
        $getReference = 'HI'.date('ydhis');
        return $getReference;
    }

    public function getReference($id, $tag='')
    {
        $getReference = 'sprillgames'.$id.'-'.$tag.uniqid().time();
        // $getReference = 'HL'.'-'.'id of the payment record-'.time();
        return $getReference;
    }

    public function getRef($tag='')
    {
        $getReference = 'sprillgames'.$tag.uniqid().time();
        // $getReference = 'HL'.'-'.'id of the payment record-'.time();
        return $getReference;
    }

    public function referralUuid()
    {
        $getUuid = uniqid();
        return $getUuid;
    }

    public function invitationUuid()
    {
        $getUuid = uniqid();
        return $getUuid;
    }
}
