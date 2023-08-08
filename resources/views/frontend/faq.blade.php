@extends('frontend.layouts.app')

@section('title', config('app.name') . ' | ' . __('frontend.navbar.about.privacy_policy'))

@section('content')
    <section id="faq" class="pt-0">
        <x-static-page>
            <x-slot name="title">
                <div class="text-center text-uppercase text-white fw-bold about-title">
                Frequently Asked Questions
                </div>
            </x-slot>
    
            <x-slot name="content">
                <div class="px-3 py-4">
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">1.	What is a comprehensive motor insurance policy?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        This policy provides the widest coverage, i.e. third party bodily injury and death, third party property loss or damage and loss or damage to your own vehicle due to accidental fire, theft or an accident.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">2.	How can I determine the sum insured for my car?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        The Sum Insured of your vehicle is an estimated value for charging your insurance premium however it is also the maximum amount for which your vehicle is insured for. The basis of assessing the true worth of your vehicle is its market value at the time of a loss. The market value must be within the Sum Insured. If it is not, you will be considered your own Insurer for the difference.
                        </div>
                        <div class="col-12">
                        Some Insurers offer Agreed Value Policies. Under this term, the market value is considered equal to the Agreed Value of the sum insured.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">3.	What are some available extensions or add-ons for my policy?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        The Motor policy allows for policyholders to extend cover to include extra benefits and additional cover apart from the standard coverage. Do communicate with your insurers to request for these additional covers.
                        </div>
                        <div class="col-12">
                            <ul>
                                <li>Flood, windstorm, rainstorm, typhoon, hurricane, volcanic eruption, earthquake, landslide/landslip, subsidence or sinking of the soil/earth or other convulsion of nature</li>
                                <li>Breakage of glass in windscreen or windows</li>
                                <li>Strike, riot and civil commotion</li>
                                <li>Tuition and testing purposes</li>
                                <li>Additional named driver</li>
                                <li>All drivers’ extension for private car polices issued to a company of businesses only</li>
                                <li>Passenger liability</li>
                                <li>Liability of passengers for acts of negligence</li>
                                <li>Additional business use</li>
                            </ul>
                        </div>
                        <div class="col-12">
                        The availability of these extensions may vary among insurance providers.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">4.	What are the exclusions in Comprehensive Motor Insurance?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        A standard motor insurance will not cover certain losses, such as your own death or bodily injury due to a motor accident, your liability against claims from passengers in your vehicle (except for passengers of hired vehicles such as taxis and buses) and loss or damage arising from an act of nature, such as flood, storm and landslide. However, you may pay additional premiums to extend your policy to cover flood, landslide and landslip as well as cover your passengers. <b>It is important to check your policy for the exclusions.</b>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">5.	What is "NCD" ?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        NCD is a ‘reward’ scheme for you if no claim was made on your insurance policy on an annual basis. Different NCD rates are applicable for different classes of vehicles. For a private car, the scale of NCD ranges from 25% to 55% as provided in the policy.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">6.	Why might the quoted NCD differ from my record?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        The quoted NCD can vary if there was a claim lodged or an NCD withdrawal requested to transfer to another car. You can check your current NCD rate here : <a href="https://www.mycarinfo.com.my/NCDCheck/Online">https://www.mycarinfo.com.my/NCDCheck/Online</a>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">7.	What is Market Value coverage?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Market Value coverage is based on the insurance company's estimate of your car's worth in the open market. In case of any incidents, your claim will be based on the current market value of your car model at that point in time.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">8.	What is Agreed Value coverage?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Agreed Value coverage is an amount agreed upon between you and your insurance company. If your car is written off or stolen, this coverage ensures you receive the agreed compensation, but it may lead to higher premiums.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">9.	What is an excess?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Most policies are subject to an excess clause. An excess is the first amount of a claim for which you will have to pay. Your insurance pays for the amount beyond the excess. The excess clause may apply on repair claims and/or on theft claims. Some insurers will overlook application of the excess if repairs are undertaken at their panel of repairers.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">10. What is loading?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Loading is an additional amount included in the insurance cost to cover individuals perceived as higher risk. It accounts for anticipated higher losses for such individuals.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">11.	How can I get the lowest premium for my car?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        To get the most affordable premium, select an insurer that fits your budget.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">12.	What happens if I cancel my policy before the contract expires?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Cancelling the policy before the contract expires may incur a cancellation fee during the one-year valid period. If considering a different insurer, it's advisable to wait until your current contract expires.
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">13.	What do I do if my car breaks down or if I get into a car accident?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        Please call your insurance provider :
                        </div>
                        <div class="col-12 mt-2">
                        <b>Allianz Insurance</b>
                        </div>
                        <div class="col-12">
                        1-800-22-5542 (24-hour Hotline) / +603-2264 0560 (calling from overseas)
                        </div>
                        <div class="col-12">
                        <a href="https://www.allianz.com.my/personal/help-and-services/how-to-and-faqs/how-to-make-a-claim/motor-claims.html">https://www.allianz.com.my/personal/help-and-services/how-to-and-faqs/how-to-make-a-claim/motor-claims.html</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>AmAssurance</b>
                        </div>
                        <div class="col-12">
                        1-800-88-6333 (24-hour Hotline)
                        </div>
                        <div class="col-12">
                        <a href="https://www.amassurance.com.my/content/amassurance-claims-centre">https://www.amassurance.com.my/content/amassurance-claims-centre</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>Liberty Insurance</b>
                        </div>
                        <div class="col-12">
                        +603-2619 9000
                        </div>
                        <div class="col-12">
                        <a href="https://www.libertyinsurance.com.my/claims/file-a-claim">https://www.libertyinsurance.com.my/claims/file-a-claim</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>P&O Insurance</b>
                        </div>
                        <div class="col-12">
                        1-811-88-2121
                        </div>
                        <div class="col-12">
                        <a href="https://www.poi2u.com/help/claim-services/">https://www.poi2u.com/help/claim-services/</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>Zurich Insurance & Zurich Takaful </b>
                        </div>
                        <div class="col-12">
                        1-300-88-6222 (24-hour Hotline ) / +603-7628 1535 (WhatsApp chat & local landline)
                        </div>
                        <div class="col-12">
                        <a href="https://www.zurich.com.my/en/customer-hub/my-claims">https://www.zurich.com.my/en/customer-hub/my-claims</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>Lonpac Insurance</b>
                        </div>
                        <div class="col-12">
                        1-300-88-1138 / Lonpac Road Assist App
                        </div>
                        <div class="col-12">
                        <a href="https://www.lonpac.com/claims/motor/private-car/accident-claim">https://www.lonpac.com/claims/motor/private-car/accident-claim</a>
                        </div>
                        <div class="col-12 mt-2">
                        <b>Berjaya Sompo Insurance</b>
                        </div>
                        <div class="col-12">
                        1 800 18 8033 (24-hour hotline)
                        </div>
                        <div class="col-12">
                        <a href="https://www.berjayasompo.com.my/product/rakan-auto">https://www.berjayasompo.com.my/product/rakan-auto</a>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-primary fw-bold mb-1">14.	Who can I contact if I have concerns regarding my claims?</p>
                    <div class="row ps-3">
                        <div class="col-12">
                        As your insurance broker, we are dedicated to safeguarding the best interests of our valued clients.  You may contact us via :
                        </div>
                        <div class="col-12 mt-2">
                        Email 	:	insta.admin@my.howdegroup.com
                        </div>
                        <div class="col-12">
                        Whatsapp 	:	+60122606183
                        </div>
                    </div>
                </div>
                </div>
            </x-slot>
        </x-static-page>
    </section>
@endsection