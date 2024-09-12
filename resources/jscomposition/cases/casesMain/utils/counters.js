export default {}

export const formatCounters = (data)=>{
    const counters = [
        {
            header: "My cases",
            body:data.myCases.toString(),
            color:"amber",
            icon:"fa-regular fa-user",
            url:'/cases-main/my-cases'
        },
        {
            header: "In progress",
            body:data.inProgress.toString(),
            color:"green",
            icon:"fa-solid fa-list",
            url:'/cases-main/in-progress'
        },
        {
            header: "Completed",
            body:data.completed.toString(),
            color:"blue",
            icon:"fa-regular fa-circle-check",
            url:'/cases-main/completed'
        },
        {
            header: "All cases",
            body:data.allCases.toString(),
            color:"purple",
            icon:"fa-regular fa-clipboard",
            url:'/cases-main/all-cases'
        },
        {
            header: "All requests",
            body:data.allRequests.toString(),
            color:"gray",
            icon:"fa-solid fa-play",
            url:()=>{
                window.location.href = "/cases"
            }
        }
    ]
    
    return counters
}
