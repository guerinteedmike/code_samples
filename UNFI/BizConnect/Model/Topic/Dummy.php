<?php
/**
 * TJR: This class exists for no other reason than a class must exist for the Topics defined in communication.xml
 * For every new message we add for communicating with MCOM we must define that topic in the communication.xml file.
 * In there one of the parameters is the 'Request' class. Normally this represents the class structure that gets
 * mapped into JSON format when publishing messages, but we aren't going to bother with that as its a lot of
 * overhead when we can just create the JSON directly.
 */
namespace UNFI\BizConnect\Model\Topic;

class Dummy {}